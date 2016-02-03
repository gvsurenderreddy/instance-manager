<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class DatabasePushCommand extends DatabaseBaseCommand
{

    protected function configure()
    {
        $this
            ->setName('database:push')
            ->setDescription('Push database to proxy account.')
            ->addArgument(
                'databaseCode',
                InputArgument::REQUIRED,
                'Database code from config'
            )
            ->addOption(
                'outputFile',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to file, where database will be dumped.'
            )
            ->addOption(
                'copyPath',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to folder on proxy server where the encrypted database will be stored.'
            )
            ->addOption(
                'iv',
                null,
                InputOption::VALUE_REQUIRED,
                'Random code used to harden encryption. Required.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setServices();

        $databaseCode = $input->getArgument('databaseCode');
        $outputFile = $input->getOption('outputFile');
        $copyPath = $input->getOption('copyPath');
        $iv = $input->getOption('iv');

        $localInstance = $this->getSilexApplication()['instance'];
        $localInstanceDatabases = $this->configurationService->get('type.database.' . $localInstance . '.options.databases');
        $localInstanceDatabaseByCode = array();
        foreach($localInstanceDatabases as $localInstanceDatabase) {
            $localInstanceDatabaseByCode[$localInstanceDatabase['code']] = $localInstanceDatabase;
        }

        if(isset($localInstanceDatabaseByCode[$databaseCode])) {
            $localDatabaseConfig = $localInstanceDatabaseByCode[$databaseCode];

            $this->setServices();

            $app = $this->getSilexApplication();
            $localDatabaseConfig = $localInstanceDatabaseByCode[$localDatabaseConfig['code']];
            $app['db.options'] = $localDatabaseConfig;
            $app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
                $app['db.options']
            ));

            $structureSqlOutputFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($outputFile . time() . rand(1, 999999)) . '_structure.sql';
            $dataSqlOutputFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($outputFile . time() . rand(1, 999999)) . '_data.sql';

            $this->commandService->exec($this->createStructureDumpCommand($structureSqlOutputFile, $localDatabaseConfig), $output);
            $this->commandService->exec($this->createDataDumpCommand($dataSqlOutputFile, $localDatabaseConfig['ignoreTables'], $localDatabaseConfig), $output);

            $packStatus = $this->packFiles(
                $outputFile,
                array(
                    $structureSqlOutputFile,
                    $dataSqlOutputFile,
                ),
                true
            );

            if (!$packStatus) {
                throw new \Exception('Unable to pack SQL files.');
            }

            $this->encryptFile($outputFile, $outputFile, $this->getEncryptPassword(), $iv);


            $crateRemoteDirCommandMask = 'ssh %s@%s %s %s mkdir -p %s';
            $createRemoteDirCommand = sprintf(
                $crateRemoteDirCommandMask,
                $this->configurationService->get('type.database.' . $localInstance . '.proxy.user'),
                $this->configurationService->get('type.database.' . $localInstance . '.proxy.host'),
                '-p ' . $this->configurationService->get('type.database.' . $localInstance . '.proxy.port'),
                $this->sshService->getPrivKeyFile() ? '-i ' . $this->sshService->getPrivKeyFile() : '',
                $this->configurationService->get('type.database.' . $localInstance . '.proxy.downloadPath')
            );

            $baseCommandMask = 'rsync -avz0 %s %s@%s:%s';

            $dirSyncCommand = sprintf(
                $baseCommandMask,
                $outputFile,
                $this->configurationService->get('type.database.' . $localInstance . '.proxy.user'),
                $this->configurationService->get('type.database.' . $localInstance . '.proxy.host'),
                $copyPath
            );

            $additionalKeyCommand = '';
            if ($this->sshService->getPrivKeyFile()) {
                $additionalKeyCommandMask = ' -e "ssh -i %s"';
                $additionalKeyCommand = sprintf(
                    $additionalKeyCommandMask,
                    $this->sshService->getPrivKeyFile()
                );
            }

            $additionalPortCommand = '';
            if (22 != $this->configurationService->get('type.database.' . $localInstance . '.proxy.port')) {
                $additionalPortCommandMask = ' -e "ssh -p %s"';
                $additionalPortCommand = sprintf(
                    $additionalPortCommandMask,
                    $this->configurationService->get('type.database.' . $localInstance . '.proxy.port')
                );
            }

            $this->commandService->exec($createRemoteDirCommand, $output);
            $this->commandService->exec($dirSyncCommand . $additionalKeyCommand . $additionalPortCommand, $output);
            unlink($outputFile);
            return true;
        }
    }

}