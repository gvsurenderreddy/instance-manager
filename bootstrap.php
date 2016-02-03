<?php
require_once __DIR__.'/../../../vendor/autoload.php';

$app = new Silex\Application();
$app->register(
    new \Knp\Provider\ConsoleServiceProvider(),
    array(
        'console.name' => 'IM - Instance Managment Component',
        'console.version' => '1.0.0',
        'console.project_directory' => __DIR__ . "/.."
    )
);

// TODO - make the path better. To work inside vendors and outside.
$app["path"] = __DIR__ . "/../../../";

$app->register(new SourceBroker\InstanceManager\Provider\ConfigServiceProvider($app["path"]));
$app->register(new SourceBroker\InstanceManager\Provider\ServiceProvider());

$app["console"]->add(new \SourceBroker\InstanceManager\Command\SshConnectCommand());
$app["console"]->add(new \SourceBroker\InstanceManager\Command\ReleaseMakeCommand());
$app["console"]->add(new \SourceBroker\InstanceManager\Command\MediaPullCommand());
$app["console"]->add(new \SourceBroker\InstanceManager\Command\MediaPushCommand());
$app["console"]->add(new \SourceBroker\InstanceManager\Command\DatabasePullCommand());
$app["console"]->add(new \SourceBroker\InstanceManager\Command\DatabasePushCommand());

return $app;