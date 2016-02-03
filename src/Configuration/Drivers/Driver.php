<?php

namespace SourceBroker\InstanceManager\Configuration\Drivers;


abstract class Driver implements SystemDriver
{

    protected $instance = null;
    protected $dbConfig = array(
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => '',
        'user' => '',
        'password' => '',
        'charset' => 'utf8',
    );

    /**
     * @return null
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param null $instance
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
    }


}