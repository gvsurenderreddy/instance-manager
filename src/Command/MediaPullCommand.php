<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MediaPullCommand extends MediaBaseCommand
{

    private $synchroMode = 'pull';

    protected function configure()
    {
        $this
            ->setName('media:pull')
            ->setDescription('Pull media from instance passed as param.')
            ->addArgument(
                'instance',
                InputArgument::REQUIRED,
                'Instance name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input);
        $instance = $input->getArgument('instance');

        $instanceUrl = $this->configurationService->get('type.media.' . $instance . '.instance.host');
        // sync media on deployment server
        if (!$this->configurationService->get('type.media.' . $instance . '.proxy.doNotCallPusher')) {
            $command = "curl -L {$instanceUrl}/index_im.php/media/{$instance} > /dev/null";
            $this->sshService->exec($command);
        }

        $foldersToSynchronize = $this->configurationService->get('type.media.' . $instance . '.options.folders');
        $localProjectRootPath = $this->getSilexApplication()["path"];

        foreach ($foldersToSynchronize as $synchroFolder) {

            $synchroMode = isset($synchroFolder['mode']) ? $synchroFolder['mode'] : 'all';

            if ($synchroMode == 'all' || $synchroMode == $this->synchroMode) {

                // support for $synchroFolder with subdirs
                $synchroFolderParts = explode('/', trim($synchroFolder['folder'], '/'));
                array_pop($synchroFolderParts);
                $localPathPart = implode('/', $synchroFolderParts);

                $remotePath = $this->normalizeFolder($this->configurationService->get('type.media.' . $instance . '.proxy.synchroPath')) . '/' . trim($synchroFolder['folder'], '/');

                $synchroFolderSizeOption = $synchroFolder['maxFileSize'] ? '--max-size=' . $synchroFolder['maxFileSize'] : '';

                $dirSyncCommand = sprintf(
                    'rsync -avz0 --copy-links ' . $synchroFolderSizeOption . ' %s@%s:%s %s',
                    $this->configurationService->get('type.media.' . $instance . '.proxy.user'),
                    $this->configurationService->get('type.media.' . $instance . '.proxy.host'),
                    $this->normalizeFolder($remotePath), // path can NOT have ending slash !
                    $this->normalizeFolder(realpath($localProjectRootPath . '/' . $localPathPart)) // path can NOT have ending slash !
                );
                $rsyncCommandExcludePart = $this->rsyncCommandExcludePart($this->configurationService->get('type.media.' . $instance . '.options.excludePatterns'), $this->synchroMode);
                $rsyncCommandKeyPart = $this->rsyncCommandKeyPart();
                $rsyncCommandPortPart = $this->rsyncCommandPortPart($instance);
                $this->commandService->exec($dirSyncCommand . $rsyncCommandExcludePart . $rsyncCommandKeyPart . $rsyncCommandPortPart, $output);
            }
        }
    }

}