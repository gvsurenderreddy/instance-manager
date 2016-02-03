<?php

namespace SourceBroker\InstanceManager\Provider;

use SourceBroker\InstanceManager\Configuration\Drivers\TYPO3Driver;
use SourceBroker\InstanceManager\Configuration\Drivers\MagentoDriver;
use SourceBroker\InstanceManager\Configuration\Drivers\Symfony2Driver;
use SourceBroker\InstanceManager\Configuration\Drivers\SystemDriver;

class CheckSystem
{
    protected $path;
    protected $driver;
    protected $TYPO3 = 'typo3conf/AdditionalConfiguration_deploy.yml';
    protected $magento = 'app/etc/deploy.yml';
    protected $symfony2 = 'app/config/deploy.yml';

    /**
     * @param $path
     * @param string $custom
     * @param SystemDriver|null $driver
     * @throws \Exception
     */
    public function __construct($path, $custom = '', SystemDriver $driver = null)
    {
        if ($custom && file_exists($path . $custom)) {
            $this->path = $path . $custom;
            $this->driver = $driver;
        } elseif (file_exists($path . $this->TYPO3)) {
            $this->path = $path . $this->TYPO3;
            $this->driver = new TYPO3Driver();
        } elseif (file_exists($path . $this->magento)) {
            $this->path = $path . $this->magento;
            $this->driver = new MagentoDriver();
        } elseif (file_exists($path . $this->symfony2)) {
            $this->path = $path . $this->symfony2;
            $this->driver = new Symfony2Driver();
        } else {
            throw new \Exception('Not found deploy configuration');
        }
    }

    /**
     * @return mixed
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param mixed $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

}