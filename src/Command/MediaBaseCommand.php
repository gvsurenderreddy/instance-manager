<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Class BaseMediaCommand
 * @package SourceBroker\InstanceManager\Command
 */
class MediaBaseCommand extends BaseCommand
{

    /**
     * @param InputInterface $input
     * @throws \Exception
     */
    protected function init(InputInterface $input)
    {
        $this->setServices();

        if (!$this->commandService->commandExists('rsync')) {
            throw new \Exception('Command "rsync" does not exists on your environment.');
        }
        $this->sshService->connect($this->configurationService->get('type.media.' . $input->getArgument('instance') . '.proxy'));
    }

    /**
     * @param $excludePatterns
     * @param $synchroMode
     * @return string
     */
    protected function rsyncCommandExcludePart($excludePatterns, $synchroMode)
    {
        $excludeFoldersCommandPart = '';
        foreach ((array)$excludePatterns as $excludePattern) {
            $mode = isset($excludePattern['mode']) ? $excludePattern['mode'] : 'all';
            if ($mode == 'all' || $mode == $synchroMode) {
                $excludePatternCaseInsensitive = '';
                $excludePatternNormalized = strtolower($excludePattern['pattern']);
                // rsync does not have case insensitive flag
                foreach (str_split($excludePatternNormalized) as $letter) {
                    if (strtoupper($letter) == $letter) {
                        $excludePatternCaseInsensitive .= $letter;
                    } else {
                        $excludePatternCaseInsensitive .= '[' . $letter . mb_strtoupper($letter) . ']';
                    }
                }
                $excludeFoldersCommandPart .= " --exclude '" . $excludePatternCaseInsensitive . "'";
            }
        }
        return $excludeFoldersCommandPart;
    }

    /**
     * @return string
     */
    protected function rsyncCommandKeyPart()
    {
        $sshCommandKeyPart = '';
        if ($this->sshService->getPrivKeyFile()) {
            $sshCommandKeyPart = sprintf(
                ' -e "ssh -i %s"',
                $this->sshService->getPrivKeyFile()
            );
        }
        return $sshCommandKeyPart;
    }

    /**
     * @param $instance
     * @return string
     */
    protected function rsyncCommandPortPart($instance)
    {
        $rsyncCommandPortPart = '';
        $instanceSshPort = $this->configurationService->get('type.media.' . $instance . '.proxy.port');
        if (22 != $instanceSshPort) {
            $rsyncCommandPortPart = sprintf(
                ' -e "ssh -p %s"',
                $instanceSshPort
            );
        }
        return $rsyncCommandPortPart;
    }

    /**
     * @param $folder
     * @return string
     */
    protected function normalizeFolder($folder)
    {
        return rtrim($folder, '/');
    }

}