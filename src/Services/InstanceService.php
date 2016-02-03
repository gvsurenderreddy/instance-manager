<?php
namespace SourceBroker\InstanceManager\Services;

/**
 * Class InstanceService
 */
class InstanceService
{

    /**
     * @var SshService
     */
    protected $SshService;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * When list of instances is get once, it will be saved here to avoid unnecessary ssh connection
     *
     * @var array|NULL
     */
    protected static $INSTANCES_WITH_DEPLOY_SCRIPT_CONFIG = NULL;

    /**
     * @param SshService $SshService
     */
    public function setSshService(SshService $SshService)
    {
        $this->SshService = $SshService;
    }

    /**
     * @param ConfigurationService $configurationService
     */
    public function setConfigurationService(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * Returns an array, which contains the names of the instances for which deploy script configuration files exists
     * on deployServer.
     *
     * @param string $deployServerName Name of the deploy server configuration
     *
     * @return array
     */
    public function getAll($deployServerName)
    {
        if (self::$INSTANCES_WITH_DEPLOY_SCRIPT_CONFIG === NULL) {
            self::$INSTANCES_WITH_DEPLOY_SCRIPT_CONFIG = $this->getAllForDeployServer($deployServerName);
        }

        return self::$INSTANCES_WITH_DEPLOY_SCRIPT_CONFIG;
    }

    /**
     * We check
     *
     * @param string $deployServerName Name of the deploy server configuration
     *
     * @return array
     */
    protected function getAllForDeployServer($deployServerName)
    {
        $this->SshService->connect($this->configurationService->get('type.deploy.' . $deployServerName . '.proxy'));
        $output = $this->SshService->exec(
            'cd ' . $this->configurationService->get('type.deploy.' . $deployServerName . '.proxy.deployPath') . 'config/deploy && ls',
            null,
            $silent = TRUE
        );

        $lines = preg_split('/\R/', $output);
        $lines = array_filter($lines);

        foreach ($lines as &$instanceFile) {
            $instanceFile = basename($instanceFile, '.rb');
        }
        return $lines;
    }

    /**
     * @param string $instance Name of the instance to check
     *
     * @return boolean
     */
    public function deployScriptConfigurationFileExists($instance)
    {
        return in_array($instance, $this->getAll($instance));
    }

    /**
     * @param string $instance Name of the instance to check
     *
     * @return boolean
     */
    public function deployServerConfigurationExists($instance)
    {
        $deployServerData = $this->configurationService->get('type.deploy.' . $instance . '.proxy');

        return is_array($deployServerData) && !!$deployServerData;
    }

    /**
     * Stops executing if there is no instance named $instance
     *
     * @param string $instance Name of the instance
     *
     * @throws \Exception
     *
     * @return void
     */
    public function stopIfNotExists($instance)
    {
        if (!$this->deployServerConfigurationExists($instance)) {
            throw new \Exception('Deployment server configuration of instance "' . $instance . '" does not exists. Please check configuration in "' . ConfigurationService::CONFIG_FILE_PATH . '"');
        }

        if (!$this->deployScriptConfigurationFileExists($instance)) {
            throw new \Exception('Deployment script configuration file of instance "' . $instance . '" does not exists.');
        }
    }
}
