<?php

namespace SourceBroker\InstanceManager\Configuration\Drivers;


class MagentoDriver extends Driver
{
    public function load($filename)
    {
        if (file_exists($filename)) {
            $xml = simplexml_load_file($filename);
            $this->dbConfig['user'] = $xml->global[0]->resources[0]->default_setup[0]->connection[0]->username[0]->__toString();
            $this->dbConfig['password'] = $xml->global[0]->resources[0]->default_setup[0]->connection[0]->password[0]->__toString();
            $this->dbConfig['dbname'] = $xml->global[0]->resources[0]->default_setup[0]->connection[0]->dbname[0]->__toString();
            $this->dbConfig['host'] = $xml->global[0]->resources[0]->default_setup[0]->connection[0]->host[0]->__toString();
            if ($xml->global[0]->resources[0]->default_setup[0]->connection[0]->port[0]) {
                $this->dbConfig['port'] = $xml->global[0]->resources[0]->default_setup[0]->connection[0]->port[0]->__toString();
            }

            if ($xml->instance_cli) {
                $this->instance = $xml->instance_cli[0]->__toString();
            } else {
                throw new \Exception('Missing instance_cli local.xml file');
            }
        } else {
            throw new \Exception('Missing "' . $filename . '" configuration file');
        }

        return $this->dbConfig;
    }

    public function supports($filename)
    {
        return (bool)preg_match('#\.xml(\.dist)?$#', $filename);
    }

}