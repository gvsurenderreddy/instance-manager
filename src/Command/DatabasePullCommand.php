<?php
namespace SourceBroker\InstanceManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasePullCommand extends DatabaseBaseCommand
{

    protected function configure()
    {
        $this
            ->setName('database:pull')
            ->setDescription('Pull database from instance passed as param.')
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
        $app = $this->getSilexApplication();

        try {

            $localInstance = $this->getSilexApplication()['instance'];
            $localInstanceDatabases = $this->configurationService->get('type.database.' . $localInstance . '.options.databases');
            $localInstanceDatabaseByCode = array();
            foreach ($localInstanceDatabases as $localInstanceDatabase) {
                $localInstanceDatabaseByCode[$localInstanceDatabase['code']] = $localInstanceDatabase;
            }
            $remoteInstanceDatabases = $this->configurationService->get('type.database.' . $instance . '.options.databases');
            foreach ((array)$remoteInstanceDatabases as $remoteInstanceDatabase) {
                if (in_array($remoteInstanceDatabase['code'], array_keys($localInstanceDatabaseByCode))) {
                    $databaseServersData = $this->configurationService->get('type.database.' . $instance . '.proxy');
                    $this->sshService->connect($databaseServersData);

                    $fileName = 'db_' . md5(time() . rand(1, 999999)) . '.zip';

                    // path of the file to download db to on the local
                    $downloadFileLocal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

                    // make sure that directory exists and is writeable
                    if (!is_dir(dirname($downloadFileLocal))) {
                        mkdir(dirname($downloadFileLocal), 0777, true);
                    }
                    // path of the file to download to on the
                    $parameters = array();
                    $downloadFileRemote = $databaseServersData['downloadPath'] . $fileName;
                    $iv = $this->createIvForMcrypt();

                    $parameters['iv'] = array('value' => $iv);
                    $parameters['code'] = array('value' => $remoteInstanceDatabase['code'], 'encrypt' => true, 'base64Encode' => true);
                    $parameters['outputFile'] = array('value' => $fileName, 'encrypt' => true, 'base64Encode' => true);
                    $parameters['copyPath'] = array('value' => $downloadFileRemote, 'encrypt' => true, 'base64Encode' => true);
                    $hashParts = array();
                    foreach ($parameters as $parameterKey => $parameter) {
                        $hashParts[] = trim($parameter['value']);
                    }
                    $parameters['hash'] = array('value' => md5(implode('-', $hashParts)), 'encrypt' => true, 'base64Encode' => true);

                    $urlPairs = array();
                    foreach ($parameters as $parameterKey => $parameter) {
                        $parameters[$parameterKey]['value'] = trim($parameters[$parameterKey]['value']);
                        if ($parameter['encrypt']) {
                            $parameters[$parameterKey]['value'] = $this->encryptData($parameter['value'], $this->getEncryptPassword(), $iv);
                        }
                        if ($parameter['base64Encode']) {
                            // With this strtr no urlencode is needed. This is important as some servers have inconsistency for GET param url decoding.
                            $parameters[$parameterKey]['value'] = strtr(base64_encode($parameters[$parameterKey]['value']), '+/=', '-_,');
                        }
                        $urlPairs[] = $parameterKey . '=' . $parameters[$parameterKey]['value'];
                    }
                    $command = "curl -L " . $this->configurationService->get('type.database.' . $instance . '.instance.host') . "/index_im.php/database/$instance -G -d \"" . implode('&', $urlPairs) . "\" > /dev/null";

                    $this->sshService->exec(
                        $command,
                        $output,
                        $silent = false,
                        array('-t')
                    );
                    $this->sshService->downloadFile($downloadFileRemote, $downloadFileLocal);

                    $this->decryptFile($downloadFileLocal, $downloadFileLocal, $this->getEncryptPassword(), $iv);

                    $unpackDirectory = dirname($downloadFileLocal) . DIRECTORY_SEPARATOR . basename($downloadFileLocal, '.zip');

                    // make sure that directory exists and is writeable
                    if (!is_dir($unpackDirectory)) {
                        mkdir($unpackDirectory, 0777, true);
                    }

                    $this->unpackFile($downloadFileLocal, $unpackDirectory, true);
                    $path = glob($unpackDirectory . DIRECTORY_SEPARATOR . '*_structure.sql');
                    $structureSqlFile = array_shift($path);
                    $path = glob($unpackDirectory . DIRECTORY_SEPARATOR . '*_data.sql');
                    $dataSqlFile = array_shift($path);

                    $this->messageService->bgCyan($structureSqlFile);
                    $this->messageService->bgCyan($dataSqlFile);

                    $localDatabaseConfig = $localInstanceDatabaseByCode[$remoteInstanceDatabase['code']];
                    $app['db.options'] = $localDatabaseConfig;
                    $app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
                        $app['db.options']
                    ));

                    if ($structureSqlFile && $dataSqlFile) {
                        $this->dropAllTables();
                        $this->commandService->exec($this->createSqlImportCommand($structureSqlFile, $localDatabaseConfig), $output);
                        $this->commandService->exec($this->createSqlImportCommand($dataSqlFile, $localDatabaseConfig), $output);
                        @unlink($structureSqlFile);
                        @unlink($dataSqlFile);
                    }

                    // post import SQL
                    $postImportSql = $localDatabaseConfig['postImportSql'];
                    if (null !== $postImportSql) {
                        /** @var \Doctrine\DBAL\Connection $conn */
                        $conn = $app['db'];
                        $conn->exec($postImportSql);
                        $this->messageService->bgCyan('Post SQL for instance ' . $localInstance . ' imported.');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->showError($e->getMessage(), $output);
        }
    }

}