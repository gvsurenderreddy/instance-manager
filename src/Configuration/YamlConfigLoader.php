<?php

namespace SourceBroker\InstanceManager\Configuration;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        // TODO - make real yml import to allow references from external files
        $yaml = file_get_contents($resource);
        // TODO - change the way the file its loaded
        $yamlDefault = file_get_contents('vendor/sourcebroker/instance-manager/src/Configuration/Default/DeployDefaults.yml');
        $yaml = str_replace('  defaults: 1', $yamlDefault, $yaml);
        $configValues = Yaml::parse($yaml);
        return $configValues;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }
}