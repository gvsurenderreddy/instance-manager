<?php

try {
    $app = require_once __DIR__.'/bootstrap.php';
    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/views',
    ));

    $app->mount('/', new \SourceBroker\InstanceManager\Controller\DeployController());
    return $app;

} catch (\Exception $e) {
    echo $e->getMessage()."\n";
}
