<?php
    require_once __DIR__ . '/../vendor/autoload.php';
    use MOSS\MOSS;

    $userid = ""; // Enter your MOSS userid
    $moss = new MOSS($userid);
    $moss->setLanguage('cc');
    $moss->addByWildcard(__DIR__.DIRECTORY_SEPARATOR.'codes/*');
    $moss->setCommentString("This is a test");
    $id=$moss->send();
    $moss->saveTo(__DIR__.DIRECTORY_SEPARATOR.'report', $id);