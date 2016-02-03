<?php
namespace SourceBroker\InstanceManager\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;


abstract class BaseCommand extends Command
{
    /**
     * @var \SourceBroker\InstanceManager\Services\CommandService
     */
    protected $commandService;

    /**
     * @var \SourceBroker\InstanceManager\Services\ConfigurationService
     */
    protected $configurationService;

    /**
     * @var \SourceBroker\InstanceManager\Services\SshService
     */
    protected $sshService;

    /**
     * @var \SourceBroker\InstanceManager\Services\InstanceService
     */
    protected $instanceService;

    /**
     * @var \SourceBroker\InstanceManager\Services\MessageService
     */
    protected $messageService;

    protected function setServices()
    {
        $this->commandService = $this->getSilexApplication()['deploy.command'];
        $this->sshService = $this->getSilexApplication()['deploy.ssh'];
        $this->configurationService = $this->getSilexApplication()['deploy.configuration'];
        $this->instanceService = $this->getSilexApplication()['deploy.instance'];
        $this->messageService = $this->getSilexApplication()['deploy.message'];

        $this->sshService->setCommandService($this->commandService);
        $this->instanceService->setConfigurationService($this->configurationService);
        $this->instanceService->setSshService($this->sshService);
        $this->commandService->setMessageService($this->messageService);
    }

    /**
     * @param $message
     * @param OutputInterface $output
     */
    protected function showError($message, OutputInterface $output)
    {
        $formatter = $this->getHelper('formatter');
        $errorMessages = array($message);
        $formattedBlock = $formatter->formatBlock($errorMessages, 'error', true);
        $output->writeln("\n");
        $output->writeln($formattedBlock);
        $output->writeln("\n");
    }
}