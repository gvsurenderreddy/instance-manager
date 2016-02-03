<?php

namespace SourceBroker\InstanceManager\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

use SourceBroker\InstanceManager\Services\CommandService;
use SourceBroker\InstanceManager\Services\ConfigurationService;
use SourceBroker\InstanceManager\Services\InstanceService;
use SourceBroker\InstanceManager\Services\MessageService;
use SourceBroker\InstanceManager\Services\SshService;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['deploy.command'] = new CommandService();
        $app['deploy.configuration'] = new ConfigurationService($app['config']);
        $app['deploy.ssh'] = new SshService();
        $app['deploy.instance'] = new InstanceService();
        $app['deploy.message'] = new MessageService();
    }

    public function boot(Application $app)
    {
    }

}