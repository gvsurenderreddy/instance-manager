<?php

namespace SourceBroker\InstanceManager\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use SourceBroker\InstanceManager\Configuration\BaseConfiguration;
use SourceBroker\InstanceManager\Configuration\YamlConfigLoader;
use SourceBroker\InstanceManager\Configuration\Drivers\SystemDriver;


/**
 * Class ConfigServiceProvider
 * @package SourceBroker\InstanceManager\Provider
 */
class ConfigServiceProvider implements ServiceProviderInterface
{


    /**
     * @var array
     */
    private $settingsTypes = array('proxy', 'instance', 'options');

    /**
     * @var CheckSystem
     */
    private $system;
    /**
     * @var mixed
     */
    private $driver;
    /**
     * @var
     */
    private $rootPath;

    /**
     * @param $rootPath
     * @param string $configPath
     * @param SystemDriver|null $driver
     */
    public function __construct($rootPath, $configPath = '', SystemDriver $driver = null)
    {
        $this->rootPath = $rootPath;
        $this->system = new CheckSystem($rootPath, $configPath, $driver);
        $this->driver = $this->system->getDriver();
    }

    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['config'] = $this->readConfig();
        $app['instance'] = $this->driver->getInstance();
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }

    /**
     * @return array
     */
    private function readConfig()
    {
        $directories = array($this->system->getPath());

        $locator = new FileLocator($directories);

        $loader = new YamlConfigLoader($locator);
        $configValues = $loader->load($locator->locate($this->system->getFileName()));

        foreach ($configValues['deploy']['type'] as $typeName => &$type) {
            foreach ($type as $instanceName => &$instance) {
                foreach ($instance as $configPartName => $configPart) {
                    switch ($typeName) {
                        case 'database':
                            if (isset($instance['options']) && isset($instance['options']['ignoreTables'])) {
                                $instance['options']['ignoreTables'] = $this->flattenYamlMergedSequenceWithScalars($instance['options']['ignoreTables']);
                            }
                            if (isset($instance['options']) && isset($instance['options']['databases'])) {
                                foreach($instance['options']['databases'] as $key => $databaseConfig) {
                                    if (isset($databaseConfig['configFile']) && strlen($databaseConfig['configFile'])) {
                                        if ($this->driver->supports($databaseConfig['configFile'])) {
                                            $databaseConfigImported = $this->driver->load($this->rootPath . $databaseConfig['configFile']);
                                            $instance['options']['databases'][$key] = array_merge($instance['options']['databases'][$key], $databaseConfigImported);
                                        } else {
                                            throw new \InvalidArgumentException(
                                                sprintf("The config file '%s' appears to have an invalid format.", $databaseConfig['configFile']));
                                        }
                                    }
                                }
                            }

                            break;

                        case 'media':
                            if (isset($instance['options'])) {
                                if (isset($instance['options']['excludePatterns'])) {
                                    $instance['options']['excludePatterns'] = $this->flattenYamlMergedSequenceWithArrays($instance['options']['excludePatterns']);
                                }
                                if (isset($instance['options']['folders'])) {
                                    $instance['options']['folders'] = $this->flattenYamlMergedSequenceWithArrays($instance['options']['folders']);
                                }
                            }
                            break;

                    }
                }
            }
        }

        $processor = new Processor();
        $configuration = new BaseConfiguration();
        $processedConfiguration = $processor->processConfiguration(
            $configuration,
            $configValues
        );
        foreach ($processedConfiguration['type'] as $typeName => &$type) {
            foreach ($type as $instanceName => &$instance) {
                    foreach ($this->settingsTypes as $configPartName) {
                        if ($instanceName != '_allInstances' && $typeName != '_allTypes') {
                            switch ($configPartName) {
                                case 'instance':
                                    $this->fallbackNode('accessIp', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('host', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('user', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('port', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    break;

                                case 'proxy':
                                    $this->fallbackNode('user', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('host', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('port', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    break;

                                case 'options':
                                    $this->fallbackNode('folders', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('excludePatterns', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('ignoreTables', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    $this->fallbackNode('databases', $instance, $instanceName, $type, $typeName, $configPartName, $processedConfiguration);
                                    break;

                                default:
                            }
                        }

                    }
                if ($instanceName != '_allInstances' && $typeName != '_allTypes') {
                    $this->checkPaths($instance);
                }
            }
        }
        return $processedConfiguration;
    }


    /**
     * @param $variable
     * @param $instance
     * @param $instanceName
     * @param $type
     * @param $typeName
     * @param $configPartName
     * @param $config
     */
    protected function fallbackNode($variable, &$instance, $instanceName, $type, $typeName, $configPartName, $config)
    {
        if (!isset($instance[$configPartName][$variable]) || empty($instance[$configPartName][$variable])) {

            $instance[$configPartName][$variable] = null;

            // fallback from _allInstances inside instance settings
            if (isset($type['_allInstances'][$configPartName][$variable])) {
                $instance[$configPartName][$variable] = $type['_allInstances'][$configPartName][$variable];
            }
            // fallback from Type->_allTypes->[instanceName]
            if (null == $instance[$configPartName][$variable] && isset($config['type']['_allTypes'][$instanceName][$configPartName][$variable])) {
                $instance[$configPartName][$variable] = $config['type']['_allTypes'][$instanceName][$configPartName][$variable];
            }
            // fallback from _allInstances  in general fallback
            if (null == $instance[$configPartName][$variable] &&  isset($config['type']['_allTypes']['_allInstances'][$configPartName][$variable])) {
                $instance[$configPartName][$variable] = $config['type']['_allTypes']['_allInstances'][$configPartName][$variable];
            }
            if (null == $instance[$configPartName][$variable] && $typeName != '_allTypes' && $instanceName != '_allInstances' && $variable == 'user') {
                $instance[$configPartName][$variable] = $typeName . '-' . $instanceName;
            }
        }

    }

    /**
     * @param $arrayToFlatten
     * @return array
     */
    protected function flattenYamlMergedSequenceWithArrays($arrayToFlatten)
    {
        $flattenArray = array();
        foreach ((array)$arrayToFlatten as $nestedArrayElement1) {
            if (is_array($nestedArrayElement1) && !is_array(array_values($nestedArrayElement1)[0])) {
                $flattenArray[] = $nestedArrayElement1;
            } else {
                foreach ((array)$nestedArrayElement1 as $nestedArrayElement2) {
                    if (!is_array(array_values($nestedArrayElement2)[0])) {
                        $flattenArray[] = $nestedArrayElement2;
                    }
                }
            }
        }
        return $flattenArray;
    }

    /**
     * @param $arrayToFlatten
     * @return array
     */
    protected function flattenYamlMergedSequenceWithScalars($arrayToFlatten)
    {
        $flattenArray = array();
        foreach ((array)$arrayToFlatten as $nestedArrayElement1) {
            if (!is_array($nestedArrayElement1)) {
                $flattenArray[] = $nestedArrayElement1;
            } else {
                foreach ($nestedArrayElement1 as $nestedArrayElement2) {
                    if (!is_array($nestedArrayElement2)) {
                        $flattenArray[] = $nestedArrayElement2;
                    }
                }
            }
        }
        return $flattenArray;
    }

    /**
     * @param $type
     */
    protected function checkPaths(&$type)
    {
        $paths = array('basePath', 'deployPath', 'downloadPath', 'synchroPath');
        foreach ($paths as $path) {
            if (isset($type['proxy'][$path])) {
                $type['proxy'][$path] = sprintf($type['proxy'][$path], $type['proxy']['user']);
            }
        }
    }
}
