<?php

namespace SourceBroker\InstanceManager\Services;

use Symfony\Component\Process\Process;


/**
 * Class CommandService
 */
class CommandService
{

    /**
     * Control if logs are outputed to std
     *
     * @var bool
     */
    protected $silent = FALSE;


    /**
     * @var MessageService
     */
    protected $messageService;


    /**
     * Do not show logs on screen
     *
     * @param $silent
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
    }


    /**
     * Do not show logs on screen
     *
     * @return bool
     */
    public function getSilent()
    {
        return $this->silent;
    }

    /**
     * @param MessageService $messageService
     */
    public function setMessageService(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Executes the command $cmd
     *
     * @param string $cmd
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param bool $silent
     * @param bool $tty
     * @return string|void
     */
    public function exec($cmd, $output = null, $silent = FALSE, $tty = FALSE)
    {

        $process = new Process($cmd);
        if ($tty) {
            $process->setTty(TRUE);
        }
        $process->setTimeout(null);
        if (!$silent && $output) {
            $output->writeln($this->messageService->lightGray('-------------------------------------------------'));
            $output->writeln($this->messageService->lightGray('Executing: ' . $cmd));
            $messageService = $this->messageService;
            $process->setTimeout(3600);
            $process->start();
            $process->wait(
                function ($type, $buffer) use ($output, $messageService) {
                    if (Process::ERR === $type) {
                        $output->writeln($messageService->red('----> ERROR START'));
                        $output->write($messageService->red('----> ' . $buffer));
                        $output->writeln($messageService->red('----> ERROR END'));
                    } else {
                        $output->write($messageService->green($buffer));
                    }
                }
            );
        } else {
            $process->run();
        }
        return $process->getOutput();
    }

    /**
     * @param string $cmd Command to check
     *
     * @return boolean
     */
    public function commandExists($cmd)
    {
        $return = shell_exec('which ' . $cmd);

        return !empty($return);
    }
}
