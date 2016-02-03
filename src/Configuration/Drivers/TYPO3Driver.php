<?php

namespace SourceBroker\InstanceManager\Configuration\Drivers;


class TYPO3Driver extends Driver
{

    public function load($filename)
    {
        if (file_exists($filename)) {
            require $filename;
            if (isset($GLOBALS['TYPO3_CONF_VARS']) && isset($GLOBALS['TYPO3_CONF_VARS']['DB'])) {
                $config = $GLOBALS['TYPO3_CONF_VARS']['DB'];
                if (isset($config['host'])) {
                    $typo_db_host_parts = explode(':', $config['host']);
                    $this->dbConfig['host'] = count($typo_db_host_parts) > 1 ? $typo_db_host_parts[0] : $config['host'];
                    $this->dbConfig['port'] = count($typo_db_host_parts) > 1 ? $typo_db_host_parts[1] : 3306;
                }

                if (isset($config['database'])) {
                    $this->dbConfig['dbname'] = $config['database'];
                }

                if (isset($config['username'])) {
                    $this->dbConfig['user'] = $config['username'];
                }

                if (isset($config['password'])) {
                    $this->dbConfig['password'] = $config['password'];
                }
            } elseif (isset($typo_db_username) && isset($typo_db_password) && isset($typo_db_host) && isset($typo_db)) {

                $typo_db_host_parts = explode(':', $typo_db_host);
                $this->dbConfig['host'] = count($typo_db_host_parts) > 1 ? $typo_db_host_parts[0] : $typo_db_host;
                $this->dbConfig['port'] = count($typo_db_host_parts) > 1 ? $typo_db_host_parts[1] : 3306;
                $this->dbConfig['dbname'] = $typo_db;
                $this->dbConfig['user'] = $typo_db_username;
                $this->dbConfig['password'] = $typo_db_password;
            }

            $contextStringParts = explode('/', getenv('TYPO3_CONTEXT'));
            $typo3ContextInstance = (isset($contextStringParts[2])) ? $contextStringParts[2] : '';
            if (isset($typo3ContextInstance) && $typo3ContextInstance) {
                $this->instance = strtolower($typo3ContextInstance);
            } else {
                throw new \Exception("\nTYPO3_CONTEXT environment variable is not set. \nIf this is your local instance then please put following line: \nputenv('TYPO3_CONTEXT=Development//Local');  \nin configuration file: typo3conf/AdditionalConfiguration_custom.php. \n\n");
            }
        } else {
            throw new \Exception('Missing "' . $filename . '" configuration file');
        }


        return $this->dbConfig;
    }

    public function supports($filename)
    {
        return (bool)preg_match('#\.php(\.dist)?$#', $filename);
    }

}