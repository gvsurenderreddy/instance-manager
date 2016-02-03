<?php

namespace SourceBroker\InstanceManager\Configuration\Drivers;

use Symfony\Component\Yaml\Yaml;


class Symfony2Driver extends Driver
{
    public function load($filename)
    {
        if (file_exists($filename)) {
            if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
                throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
            }
            $config = Yaml::parse($filename);
            if (isset($config['parameters'])) {
                if (isset($config['parameters']['database_host'])) {
                    $this->dbConfig['host'] = $config['parameters']['database_host'];
                }

                if (isset($config['parameters']['database_name'])) {
                    $this->dbConfig['dbname'] = $config['parameters']['database_name'];
                }

                if (isset($config['parameters']['database_user'])) {
                    $this->dbConfig['user'] = $config['parameters']['database_user'];
                }

                if (isset($config['parameters']['database_password'])) {
                    $this->dbConfig['password'] = $config['parameters']['database_password'];
                }

                if (isset($config['parameters']['database_port']) && $config['parameters']['database_port']) {
                    $this->dbConfig['port'] = $config['parameters']['database_port'];
                }

                if (isset($config['parameters']['instance_cli']) && $config['parameters']['instance_cli']) {
                    $this->instance = $config['parameters']['instance_cli'];
                } else {
                    throw new \Exception('Missing instance_cli parameter in parameters.yml file');
                }
            }
        } else {
            throw new \Exception('Missing "' . $filename . '" configuration file');
        }

        return $this->dbConfig;
    }

    public function supports($filename)
    {
        return (bool)preg_match('#\.ya?ml(\.dist)?$#', $filename);
    }

}

