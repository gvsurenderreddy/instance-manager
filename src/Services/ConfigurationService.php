<?php
namespace SourceBroker\InstanceManager\Services;

/**
 * Class ConfigurationService
 */
class ConfigurationService
{
    const LOCAL_CONFIG_FILE_PATH='';
    const CONFIG_FILE_PATH='';

    /**
     * @var array
     */
    protected static $CONFIG = array();

    /**
     * @var bool
     */
    protected static $CONFIG_PARSED = FALSE;

    public function __construct($config)
    {
        self::$CONFIG = $config;
    }

    /**
     * Parses config and set to static $CONFIG variable
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function parseConfig()
    {

        try {
            self::$CONFIG;
        } catch (\Exception $e) {
            throw new \Exception("Deployment configuration does not exist");
        }

        self::$CONFIG_PARSED = TRUE;

        $this->postParseConfig();
    }

    /**
     * Adjusts the self::$CONFIG settings after loading them from configuration files
     *
     * @return void
     */
    protected function postParseConfig()
    {
        // use self::$CONFIG['ssh']['privKeyFile'] (if is set) as key file path for all instances if they don't have
        // defined it's own private key file in 'privKeyFile' element.
        if (isset(self::$CONFIG['type']['deploy']) && is_array(self::$CONFIG['type']['deploy'])) {
            foreach (self::$CONFIG['type']['deploy'] as &$deployServer) {
                if (!isset($deployServer['privKeyFile']) && isset(self::$CONFIG['type']['ssh']['privKeyFile'])) {
                    $deployServer['privKeyFile'] = self::$CONFIG['type']['ssh']['privKeyFile'];
                }
            }
        }
    }

    /**
     * @param string $name Name of the variable in a dot style
     *
     * @return mixed
     */
    public function get($name)
    {
        if (!self::$CONFIG_PARSED) {
            $this->parseConfig();
        }

        return $this->getNestedVar(self::$CONFIG, $name);
    }

    /**
     * @param mixed $context
     * @param string $name
     *
     * @link http://stackoverflow.com/a/2287029
     *
     * @return mixed
     */
    protected function getNestedVar(&$context, $name)
    {
        $pieces = explode('.', $name);
        foreach ($pieces as $piece) {
            if (!is_array($context) || !array_key_exists($piece, $context)) {
                return NULL;
            }
            $context = &$context[$piece];
        }
        return $context;
    }
}
