<?php
namespace SourceBroker\InstanceManager\Command;


/**
 * Class DatabaseBaseCommand
 * @package SourceBroker\InstanceManager\Command
 */
abstract class DatabaseBaseCommand extends BaseCommand
{
    /**
     * Possible executable paths for mysqldump
     *
     * @var array
     */
    static $MYSQLDUMP_EXECUTABLE_PATHS = array(
        'mysqldump',
        '/usr/local/mysqldump',
        '/usr/local/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
    );

    /**
     * Possible executable paths for mysqldump
     *
     * @var array
     */
    static $MYSQL_EXECUTABLE_PATHS = array(
        'mysql',
        '/usr/local/mysql',
        '/usr/local/bin/mysql',
        '/usr/local/mysql/bin/mysql',
    );

    /**
     * Default port used by MySQL
     *
     * @var int
     */
    const MYSQL_DEFAULT_PORT = 3306;

    /**
     * @param string $outputFile
     *
     * @param $databaseConfig
     * @return string
     * @throws \Exception
     */
    protected function createStructureDumpCommand($outputFile, $databaseConfig)
    {
        $dbHost = $databaseConfig['host'];
        $dbPort = (isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : self::MYSQL_DEFAULT_PORT;

        return sprintf(
            '%s --no-data=true --default-character-set=utf8 -h%s -P%s -u%s -p%s %s -r %s',
            $this->getExecuteableMysqldump(),
            $dbHost,
            $dbPort,
            $databaseConfig['user'],
            $databaseConfig['password'],
            $databaseConfig['dbname'],
            $outputFile
        );
    }

    /**
     * @param $outputFile
     *
     * @param $instance
     * @param $databaseConfig
     * @return string
     * @throws \Exception
     */
    protected function createDataDumpCommand($outputFile, $ignoreTables, $databaseConfig)
    {
        $dbHost = $databaseConfig['host'];
        $dbPort = (isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : self::MYSQL_DEFAULT_PORT;
        $ignoreTables = $this->getIgnoreTables($ignoreTables);
        $ignoreTablesCmd = ($ignoreTables) ? '--ignore-table=' . $databaseConfig['dbname'] . '.%s' : '%s';

        return sprintf(
            '%s --create-options -e -K -q -n --default-character-set=utf8 -h%s -P%s -u%s -p%s %s -r %s ' . $ignoreTablesCmd,
            $this->getExecuteableMysqldump(),
            $dbHost,
            $dbPort,
            $databaseConfig['user'],
            $databaseConfig['password'],
            $databaseConfig['dbname'],
            $outputFile,
            implode(' --ignore-table=' . $databaseConfig['dbname'] . '.', $ignoreTables)
        );
    }

    /**
     * @param string $inputFile
     *
     * @param $databaseConfig
     * @return string
     * @throws \Exception
     */
    protected function createSqlImportCommand($inputFile, $databaseConfig)
    {
        $dbHost = $databaseConfig['host'];
        $dbPort = (isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : self::MYSQL_DEFAULT_PORT;

        return sprintf(
            'export MYSQL_PWD="%s" && %s --default-character-set=utf8 -h%s -P%s -u%s -D%s -e "SOURCE %s" ',
            $databaseConfig['password'],
            $this->getExecuteableMysql(),
            $dbHost,
            $dbPort,
            $databaseConfig['user'],
            $databaseConfig['dbname'],
            $inputFile
        );
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    protected function getExecuteableMysqldump()
    {
        foreach (self::$MYSQLDUMP_EXECUTABLE_PATHS as $executablePath) {
            if ($this->commandService->commandExists($executablePath)) {
                return $executablePath;
            }
        }

        throw new \Exception('Could not find executable mysqldump');
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    protected function getExecuteableMysql()
    {
        foreach (self::$MYSQL_EXECUTABLE_PATHS as $executablePath) {
            if ($this->commandService->commandExists($executablePath)) {
                return $executablePath;
            }
        }

        throw new \Exception('Could not find executable mysql');
    }

    /**
     * Returns array of the names of ignored tables
     *
     * @return \string[]
     */
    protected function getIgnoreTables($ignoreTables)
    {
        $allTables = $this->getAllTables();

        $it = array();

        foreach ($ignoreTables as $ignoreTableName) {
            $regexp = false;

            if (preg_match('/^[\/\#\+\%\~]/', $ignoreTableName) && $this->isRegexp($ignoreTableName)) {
                // check if pattern starts with most popular delimiters and is good regexp
                $regexp = $ignoreTableName;
            } elseif ($this->isRegexp('/' . $ignoreTableName . '/')) {
                // allow to list patterns without delimiters
                $regexp = '/^' . $ignoreTableName . '$/i';
            }

            if ($regexp) {
                $it = array_merge($it, preg_grep($regexp, $allTables));
            } elseif (in_array($ignoreTableName, $allTables)) {
                // strict names
                $it[] = $ignoreTableName;
            }
        }

        return array_unique($it);
    }

    /**
     * Returns array of the names of all tables in the database
     *
     * @return string[]
     */
    protected function getAllTables()
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->getSilexApplication()['db'];
        $sm = $conn->getSchemaManager();
        return $sm->listTableNames();
    }

    /**
     * Drop all tables from database
     *
     * @return void
     */
    protected function dropAllTables()
    {
        $tables = $this->getAllTables();
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->getSilexApplication()['db'];
        $conn->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $conn->exec('DROP TABLE IF EXISTS ' . $table);
        }

        $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param string $outputFileName
     * @param string[] $filesToPack
     * @param boolean $deleteOriginal
     *
     * @throws \Exception
     *
     * @return boolean
     */
    protected function packFiles($outputFileName, $filesToPack, $deleteOriginal)
    {
        @mkdir(dirname($outputFileName));
        if (empty($filesToPack) || is_file($outputFileName) || !is_dir(dirname($outputFileName))) {
            return false;
        }

        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            $zip->open($outputFileName, \ZipArchive::CREATE);
            foreach ((array)$filesToPack as $sourceFile) {
                $zip->addFile($sourceFile, basename($sourceFile));
            }
            $zip->close();
        } elseif ($this->commandService->commandExists('zip')) {
            $this->commandService->exec('zip -j -q ' . $outputFileName . ' ' . implode(' ', $filesToPack));
        } else {
            // delete files before exit even if no success
            if ($deleteOriginal) {
                foreach ((array)$filesToPack as $fileToPack) {
                    @unlink($fileToPack);
                }
            }

            throw new \Exception('Can not create zip file - class ZipArchive and CLI zip command are not accessible');
        }

        if ($deleteOriginal) {
            foreach ((array)$filesToPack as $fileToPack) {
                @unlink($fileToPack);
            }
        }

        return true;
    }

    /**
     * @param string $inputFile Path to the file to unpack
     * @param string $outputPath Path to unpack files to
     * @param bool $deleteOriginal Delete $inputFile after operation
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function unpackFile($inputFile, $outputPath, $deleteOriginal = false)
    {
        if (!is_file($inputFile) || !is_dir($outputPath)) {
            return false;
        }

        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive;
            $res = $zip->open($inputFile);
            if ($res === true) {
                $zip->extractTo($outputPath);
                $zip->close();
            } else {
                throw new \Exception('Can not open zip file:' . $inputFile);
            }
        } elseif ($this->commandService->commandExists('unzip')) {
            $this->commandService->exec('unzip ' . $inputFile . ' -d ' . $outputPath);
        } else {
            // delete file before exit even if no success
            if ($deleteOriginal) {
                @unlink($inputFile);
            }

            throw new \Exception('Can not unzip file - class ZipArchive and CLI unzip command are not accessible');
        }

        if ($deleteOriginal) {
            @unlink($inputFile);
        }

        return true;
    }

    /**
     * Encrypt file using mcrypt library
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $password
     * @param string $iv
     *
     * @throws \Exception
     *
     * @return boolean
     */
    protected function encryptFile($inputFile, $outputFile, $password, $iv)
    {
        if (!is_file($inputFile)) {
            throw new \Exception('File "' . $inputFile . '" does not exist, so it can not be encrypted.');
        }

        if (!function_exists('mcrypt_encrypt')) {
            throw new \Exception('"mcrypt_encrypt" function not found - probably mcrypt module is not installed.');
        }

        $data = file_get_contents($inputFile);
        return (bool)file_put_contents($outputFile, $this->decryptData($data, $password, $iv));
    }

    /**
     * Encrypt file using mcrypt library
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $password
     * @param string $iv
     *
     * @throws \Exception
     *
     * @return boolean
     */
    protected function decryptFile($inputFile, $outputFile, $password, $iv)
    {
        if (!is_file($inputFile)) {
            throw new \Exception('File "' . $inputFile . '" does not exist, so it can not be decrypted.');
        }

        if (!function_exists('mcrypt_decrypt')) {
            throw new \Exception('"mcrypt_decrypt" function not found - probably mcrypt module is not installed.');
        }

        $data = file_get_contents($inputFile);
        return (bool)file_put_contents($outputFile, $this->encryptData($data, $password, $iv));
    }

    /**
     * @param $data
     * @param $password
     * @param $iv
     * @return string
     */
    protected function encryptData($data, $password, $iv)
    {
        return mcrypt_encrypt(MCRYPT_BLOWFISH, $password, $data, MCRYPT_MODE_CBC, $iv);
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

    /**
     * @return string
     */
    protected function getEncryptPassword()
    {
        return $this->configurationService->get('secret');
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    protected function createIvForMcrypt()
    {
        return (string)rand(10000000, 99999999);
    }

    /**
     * @param string $str
     *
     * @link http://stackoverflow.com/questions/4440626/how-can-i-validate-regex/12941133#12941133
     *
     * @return bool
     */
    protected function isRegexp($str)
    {
        return (@preg_match($str, null) !== false);
    }
}