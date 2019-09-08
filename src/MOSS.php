<?php
 
namespace MOSS;

use Exception;
use KubAT\PhpSimple\HtmlDomParser;

class MOSS
{
    private $allowed_languages = ["c", "cc", "java", "ml", "pascal", "ada", "lisp", "scheme", "haskell", "fortran", "ascii", "vhdl", "perl", "matlab", "python", "mips", "prolog", "spice", "vb", "csharp", "modula2", "a8086", "javascript", "plsql", "verilog"];
    private $options = [];
    private $basefiles = [];
    private $files = [];
    private $server;
    private $port;
    private $userid;
    private $color=['#f00','#0f0','#00f','#0ff','#f0f'];
    
    /**
     * @param int  	  $userid
     * @param string  $server
     * @param integer $port
     */
    public function __construct($userid, $server = "moss.stanford.edu", $port = 7690)
    {
        $this->options['m'] = 10;
        $this->options['d'] = 0;
        $this->options['n'] = 250;
        $this->options['x'] = 0;
        $this->options['c'] = "";
        $this->options['l'] = "c";
        $this->server = $server;
        $this->port = $port;
        $this->userid = $userid;
    }

    /**
     * set the language of the source files
     * @param string $lang
     */
    public function setLanguage($lang)
    {
        if (in_array($lang, $this->allowed_languages)) {
            $this->options['l'] = $lang;
            return true;
        } else {
            throw new Exception("Unsupported language", 1);
        }
    }

    /**
     * get a list with all supported languages
     * @return array
     */
    public function getAllowedLanguages()
    {
        return $this->allowed_languages;
    }

    /**
     * Enable Directory-Mode
     * @see -d in MOSS-Documentation
     * @param bool $enabled
     */
    public function setDirectoryMode($enabled)
    {
        if (is_bool($enabled)) {
            $this->options['d'] = (int)$enabled;
            return true;
        } else {
            throw new Exception("DirectoryMode must be a boolean", 2);
        }
    }

    /**
     * Add a basefile
     * @see -b in MOSS-Documentation
     * @param string $file
     */
    public function addBaseFile($file)
    {
        if (file_exists($file) && is_readable($file)) {
            $this->basefiles[] = $file;
            return true;
        } else {
            throw new Exception("Can't find or read the basefile (".$file.")", 3);
        }
    }

    /**
     * Occurences of a string over the limit will be ignored
     * @see -m in MOSS-Documentation
     * @param int $limit
     */
    public function setIngoreLimit($limit)
    {
        if (is_int($limit) && $limit > 1) {
            $this->options['m'] = (int)$limit;
            return true;
        } else {
            throw new Exception("The limit needs to be greater than 1", 4);
        }
    }

    /**
     * Set the comment for the request
     * @see -s in MOSS-Documentation
     * @param string $comment
     */
    public function setCommentString($comment)
    {
        $this->options['c'] = $comment;
        return true;
    }

    /**
     * Set the number of results
     * @see -n in MOSS-Documentation
     * @param int $limit
     */
    public function setResultLimit($limit)
    {
        if (is_int($limit) && $limit > 1) {
            $this->options['n'] = (int)$limit;
            return true;
        } else {
            throw new Exception("The limit needs to be greater than 1", 5);
        }
    }

    /**
     * Enable the Experimental Server
     * @see -x in MOSS-Documentation
     * @param bool $enabled
     */
    public function setExperimentalServer($enabled)
    {
        if (is_bool($enabled)) {
            $this->options['x'] = (int)$enabled;
            return true;
        } else {
            throw new Exception("Needs to be a boolean", 6);
        }
    }

    /**
     * Add a file to the request
     * @param string $file
     */
    public function addFile($file)
    {
        if (file_exists($file) && is_readable($file)) {
            $this->files[] = $file;
            return true;
        } else {
            throw new Exception("Can't find or read the file (".$file.")", 7);
        }
    }

    /**
     * Add files by a wildcard
     * @example addByWildcard("/files/*.c")
     * @param string $path
     */
    public function addByWildcard($path)
    {
        foreach (glob($path) as $file) {
            $this->addFile($file);
        }
    }

    /**
     * Send the request to the server and wait for the response
     * @return string
     */
    public function send()
    {
        $socket = fsockopen($this->server, $this->port, $errno, $errstr);
        if (!$socket) {
            throw new Exception("Socket-Error: ".$errstr." (".$errno.")", 8);
        } else {
            fwrite($socket, "moss ".$this->userid."\n");
            fwrite($socket, "directory ".$this->options['d']."\n");
            fwrite($socket, "X ".$this->options['x']."\n");
            fwrite($socket, "maxmatches ".$this->options['m']."\n");
            fwrite($socket, "show ".$this->options['n']."\n");
            //Language Check
            fwrite($socket, "language ".$this->options['l']."\n");
            $read = trim(fgets($socket));
            if ($read == "no") {
                fwrite($socket, "end\n");
                fclose($socket);
                throw new Exception("Unsupported language", 1);
            }
            foreach ($this->basefiles as $bfile) {
                $this->uploadFile($socket, $bfile, 0);
            }
            $i = 1;
            foreach ($this->files as $file) {
                $this->uploadFile($socket, $file, $i);
                $i++;
            }
            fwrite($socket, "query 0 ".$this->options['c']."\n");
            $read = fgets($socket);
            fwrite($socket, "end\n");
            fclose($socket);
            if($read===false) throw new Exception("Unexpected Error", -1);
            return (int)explode('results/', $read)[1];
        }
    }

    /**
     * Save MOSS report to local
     * @return bool
     */
    public function saveTo($path, $id)
    {
        $url="http://$this->server/results/$id";
        $generalPage=HtmlDomParser::str_get_html(file_get_contents($url), true, true, DEFAULT_TARGET_CHARSET, false);
        $table=$generalPage->find('table', 0);
        if(is_null($table)) throw new Exception('Report Not Found');
        if(!is_dir($path)) mkdir($path);
        foreach($table->find('a') as $a){
            $a->href=explode("/results/$id/",$a->href)[1];
        }
        file_put_contents($path.DIRECTORY_SEPARATOR.'index.html', $table->outertext);
        $this->fetchDetails($path, $id, count($table->find('a'))/2);
    }

    private function fetchDetails($path, $id, $count)
    {
        foreach (range(0,$count-1) as $case){
            foreach (range(0,1) as $index){
                $url="http://$this->server/results/$id/match$case-$index.html";
                $detailedPage=HtmlDomParser::str_get_html(file_get_contents($url), true, true, DEFAULT_TARGET_CHARSET, false);
                $code=$detailedPage->find('pre', 0);
                foreach($code->find('img') as $img){
                    [$color, $percentage]=explode('_',explode('.gif', explode('bitmaps/tm_', $img->src)[1])[0]);
                    $color=$this->color[(int)$color];
                    $img->tag="div";
                    $img->style="
                        background-image: linear-gradient(to right, $color $percentage%,#fff $percentage%);
                        border: 1px solid $color;
                        width: 60px;
                        height: 12px;
                    ";
                }
                file_put_contents($path.DIRECTORY_SEPARATOR."match$case-$index.html", $code->outertext);
            }
            file_put_contents($path.DIRECTORY_SEPARATOR."match$case.html", '
                <frameset cols="50%,50%" rows="100%"><frame src="match0-0.html" name="0"><frame src="match0-1.html" name="1"></frameset>
            ');
        }
    }
    
    /**
     * Upload a file to the server
     * @param  socket $handle A handle from fsockopen
     * @param  string $file   The Path of the file
     * @param  int $id     0 = Basefile, incrementing for every normal file
     * @return void
     */
    private function uploadFile($handle, $file, $id)
    {
        $size = filesize($file);
        $file_name_fixed = str_replace(" ", "_", $file);
        fwrite($handle, "file ".$id." ".$this->options['l']." ".$size." ".$file_name_fixed."\n");
        fwrite($handle, file_get_contents($file));
    }
}
