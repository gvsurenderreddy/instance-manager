<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MediaPushCommand extends MediaBaseCommand
{
    private $synchroMode = 'push';

    protected function configure()
    {
        $this
            ->setName('media:push')
            ->setDescription('Push media to proxy account.')
            ->addArgument(
                'instance',
                InputArgument::REQUIRED,
                'Instance name'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input);
        $instance = $input->getArgument('instance');

        $synchroFolders = $this->configurationService->get('type.media.' . $instance . '.options.folders');
        $projectRootPath = $this->normalizeFolder($this->getSilexApplication()["path"]);

        foreach ($synchroFolders as $synchroFolder) {
            $synchroMode = isset($synchroFolder['mode']) ? $synchroFolder['mode'] : 'all';

            if ($synchroMode == 'all' || $synchroMode == $this->synchroMode) {

                $synchroFolder['folder'] = trim($synchroFolder['folder'], '/');

                // support for $synchroFolder with subdirs
                $synchroFolderParts = explode('/', $synchroFolder['folder']);
                array_pop($synchroFolderParts);
                $remotePathPart = implode('/', $synchroFolderParts);

                $synchroFolderSizeOption = isset($synchroFolder['maxFileSize']) ? '--max-size=' . $synchroFolder['maxFileSize'] : '';

                $localFolderToSynchro = realpath($projectRootPath . '/' . $synchroFolder['folder']);
                if (FALSE !== $localFolderToSynchro) {
                    $dirSyncCommand = sprintf(
                        'rsync -avz0 --copy-links ' . $synchroFolderSizeOption . ' %s %s@%s:%s',
                        $this->normalizeFolder($localFolderToSynchro), // path can NOT have ending slash !
                        $this->configurationService->get('type.media.' . $instance . '.proxy.user'),
                        $this->configurationService->get('type.media.' . $instance . '.proxy.host'),
                        $this->normalizeFolder($this->normalizeFolder($this->configurationService->get('type.media.' . $instance . '.proxy.synchroPath')) . '/' . $remotePathPart)  // path can NOT have ending slash !
                    );

                    $rsyncCommandExcludePart = $this->rsyncCommandExcludePart($this->configurationService->get('type.media.' . $instance . '.options.excludePatterns'), $this->synchroMode);
                    $rsyncCommandKeyPart = $this->rsyncCommandKeyPart();
                    $rsyncCommandPortPart = $this->rsyncCommandPortPart($instance);
                    $this->commandService->exec($dirSyncCommand . $rsyncCommandKeyPart . $rsyncCommandExcludePart . $rsyncCommandPortPart, $output);
                }
            }
        }
    }
}