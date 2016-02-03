<?php

namespace SourceBroker\InstanceManager\Configuration\Drivers;

interface SystemDriver
{

    function load($filename);

    function supports($filename);

}