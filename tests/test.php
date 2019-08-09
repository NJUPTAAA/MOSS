<?php
    require_once __DIR__ . '/../vendor/autoload.php';
    use MOSS\MOSS;
    $userid = ""; // Enter your MOSS userid
    $moss = new MOSS($userid);
    $moss->setLanguage('java');
    $moss->addByWildcard('test/codes/*');
    $moss->setCommentString("This is a test");
    echo json_decode($moss->send(), JSON_PRETTY_PRINT);