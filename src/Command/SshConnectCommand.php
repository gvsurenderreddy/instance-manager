<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SshConnectCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('ssh:connect')
            ->setDescription('Make ssh connection to instance passed as param.')
            ->addArgument(
                'instance',
                InputArgument::REQUIRED,
                'Instance name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setServices();
        try {
            $instance = $input->getArgument('instance');

            $instanceAccountData = $this->configurationService->get('type.access.' . $instance . '.instance');
            $proxyAccountData = $this->configurationService->get('type.access.' . $instance . '.proxy');
            $this->sshService->connect($proxyAccountData);
            $this->sshService->getCommandService()->setSilent(FALSE);
            // TODO - use sshService method getBaseSSHCommand to build this $command
            $command = 'ssh -p' . ($instanceAccountData['port'] ? $instanceAccountData['port']: '22') . ' ' . $instanceAccountData['user'] . '@' . $instanceAccountData['host'] .' -t';
            $this->sshService->exec($command, $output, $silent = false, array('-t'));
        } catch (\Exception $e) {
            $this->showError($e->getMessage(), $output);
        }
    }

}