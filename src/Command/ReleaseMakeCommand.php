<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReleaseMakeCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('release:make')
            ->setDescription('Make new application release on instance passed as param.')
            ->addArgument(
                'instance',
                InputArgument::REQUIRED,
                'Instance name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setServices();

        $instance = $input->getArgument('instance');

        $this->instanceService->stopIfNotExists($instance);

        $deployServerData = $this->configurationService->get('type.deploy.' . $instance . '.proxy');
        $this->sshService->connect($deployServerData);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Do you really want to make a deploy to ' . $this->messageService->red(
                mb_strtoupper($instance)
            ) . '?! [y/n]', false
        );

        if ($helper->ask($input, $output, $question)) {
            $this->sshService->exec('cd ' . $deployServerData['deployPath'] . ' && cap ' . $instance . ' deploy', $output, $silent = false, array('-t'));
        } else {
            echo $this->messageService->yellow('Next time think it twice before you wake me up! :)' . PHP_EOL);
        }

    }

}