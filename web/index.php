<?php

if (version_compare(PHP_VERSION, '5.3.2') < 0) {
    die("App requires PHP <u>5.3.2</u> or higher. You have PHP <i>" . PHP_VERSION . "</i>.");
}

$app = require_once __DIR__.'/../app.php';
$app->run();