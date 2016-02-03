<?php

namespace SourceBroker\InstanceManager\Controller;

use Silex\Application;
use Silex\Route;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

use Symfony\Component\HttpFoundation\Request;

class DeployController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controller = new ControllerCollection(new Route());
        $controller->get('/media/{instance}', function ($instance) use ($app) {

            if (!preg_match('/^[a-z]+$/', $instance)) {
                die('Wrong instance name. Should be [a-z]+');
            }

            if (!$this->compareIp($_SERVER['REMOTE_ADDR'], $app['config']['type']['media'][$instance]['instance']['accessIp'])) {
                die('Access denied.');
            }

            /** @var \Knp\Console\Application $application */
            $application = $app['console'];
            $application->setAutoExit(false);

            $input = new ArrayInput(array(
                'command' => 'media:push',
                'instance' => $instance,
            ));

            $output = new BufferedOutput(
                OutputInterface::VERBOSITY_NORMAL,
                true // true for decorated
            );

            $application->run($input, $output);

            $converter = new AnsiToHtmlConverter();
            $content = $output->fetch();

            return $app['twig']->render('console.twig', array(
                'response' => $converter->convert($content)
            ));

        });

        $controller->get('/database/{instance}', function ($instance, Request $request) use ($app) {

            if (!preg_match('/^[a-z]+$/', $instance)) {
                die('Wrong instance name. Should be [a-z]+');
            }
            if (!$this->compareIp($_SERVER['REMOTE_ADDR'], $app['config']['type']['database'][$instance]['instance']['accessIp'])) {
                die('Access denied.');
            }
            $iv = $request->get('iv');
            if (!preg_match('/^[0-9]+$/', $iv)) {
                die('Wrong $iv. Should be [0-9]+');
            }

            if ($request->get('outputFile') && $request->get('iv') && $request->get('copyPath') && $request->get('hash')) {

                $secret = $app['config']['secret'];

                $parameters['iv'] = array('value' => null, 'decrypt' => false, 'base64Decode' => false);
                $parameters['code'] = array('value' => null, 'decrypt' => true, 'base64Decode' => true);
                $parameters['outputFile'] = array('value' => null, 'decrypt' => true, 'base64Decode' => true);
                $parameters['copyPath'] = array('value' => null, 'decrypt' => true, 'base64Decode' => true);
                $parameters['hash'] = array('value' => null, 'decrypt' => true, 'base64Decode' => true);

                foreach ($parameters as $parameterKey => $parameter) {
                    $passedValue = $request->get($parameterKey);
                    if ($parameter['base64Decode']) {
                        $passedValue = base64_decode(strtr($passedValue, '-_,', '+/='));
                    }
                    if ($parameter['decrypt']) {
                        $passedValue = $this->decryptData($passedValue, $secret, $iv);
                    }
                    $parameters[$parameterKey]['value'] = trim($passedValue);
                }

                // check security hash for external parameters
                $hashToCheck = $parameters['hash']['value'];
                unset($parameters['hash']);
                $hashParts = array();
                foreach ($parameters as $parameterKey => $parameter) {
                    $hashParts[] = $parameter['value'];
                }
                if ($hashToCheck != md5(implode('-', $hashParts))) {
                    die('Security hash invalid!');
                }

                /** @var \Knp\Console\Application $application */
                $application = $app['console'];
                $application->setAutoExit(false);

                $input = new ArrayInput(array(
                    'command' => 'database:push',
                    'databaseCode' => $parameters['code']['value'],
                    '--outputFile' => $parameters['outputFile']['value'],
                    '--iv' => $iv,
                    '--copyPath' => $parameters['copyPath']['value']
                ));

                $output = new BufferedOutput(
                    OutputInterface::VERBOSITY_NORMAL,
                    true // true for decorated
                );

                $application->run($input, $output);

                $converter = new AnsiToHtmlConverter();
                $content = $output->fetch();

                return $app['twig']->render('console.twig', array(
                    'response' => $converter->convert($content)
                ));

            } else {
                return false;
            }

        });

        return $controller;
    }

    /**
     * @param $ipToCheck
     * @param $ips
     * @return bool
     */
    private function compareIp($ipToCheck, $ips)
    {
        $comparisionResult = false;
        foreach ((array)$ips as $ip) {
            $ip = str_replace(array('...', '..'), '', str_replace('*', '', $ip));
            if (strpos($ipToCheck, $ip) !== FALSE) {
                $comparisionResult = true;
            }
        }
        return $comparisionResult;
    }


    /**
     * @param $data
     * @param $password
     * @param $iv
     * @return string
     */
    protected function decryptData($data, $password, $iv)
    {
        return mcrypt_decrypt(MCRYPT_BLOWFISH, $password, $data, MCRYPT_MODE_CBC, $iv);
    }
}