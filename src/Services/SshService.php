<?php
namespace SourceBroker\InstanceManager\Services;


/**
 * Class SshService
 */
class SshService
{

    /**
     * Host to connect to
     *
     * @var string
     */
    protected $host;

    /**
     * User account to connect to
     *
     * @var string
     */
    protected $user;

    /**
     * Connection port
     *
     * @var int
     */
    protected $port = 22;

    /**
     * Path to the file in which private key is stored.
     * If empty - default private keys will be used
     *
     * @var string
     */
    protected $privKeyFile = '';

    /**
     * Stores executed commands
     *
     * @var array
     */
    protected static $execLogs = array();

    /**
     * If TRUE, logs will be stored
     *
     * @var bool
     */
    protected $enableExecLogs = TRUE;

    /**
     * @var CommandService
     */
    protected $commandService;

    /**
     * @return CommandService
     */
    public function getCommandService()
    {
        return $this->commandService;
    }

    /**
     * @param CommandService $commandService
     */
    public function setCommandService(CommandService $commandService)
    {
        $this->commandService = $commandService;
    }

    /**
     * @param array $settings Array with elements:
     *    - host - name of the host or ip
     *    - user - user account to connect to
     *    - port - port used for ssh connection
     *    - privKeyFile - private key file path
     *
     * @throws \Exception
     */
    public function connect(array $settings)
    {
        if (!isset($settings['host']) || empty($settings['host'])) {
            throw new \Exception('Host is not set for the SSH connection');
        }

        if (!isset($settings) || empty($settings['user'])) {
            throw new \Exception('User is not set for the SSH connection');
        }

        $this->host = $settings['host'];
        $this->user = $settings['user'];

        if (isset($settings['port']) || !empty($settings['port'])) {
            $this->port = $settings['port'];
        }

        if (isset($settings['privKeyFile'])) {
            if (!is_file($settings['privKeyFile'])) {
                throw new \Exception('You specified custom SSH private key file ("' . $settings['privKeyFile'] . '"), but this file does not exist');
            }

            $this->privKeyFile = $settings['privKeyFile'];
        }
    }

    /**
     * Executes a command on remote server
     *
     * @param string $command Command to execute
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param bool $silent
     * @param array $sshAdditionalParams
     * @throws \Exception
     * @return string|void
     */
    public function exec($command, $output = null, $silent = NULL, $sshAdditionalParams = array())
    {
        if (NULL === $silent) {
            $silent = $this->commandService->getSilent();
        }
        $this->stopIfNotConnected();

        $tty = in_array('-t', $sshAdditionalParams) ? TRUE : FALSE;

        $this->logExecutionOnRemote($command, 'START');
        $result = $this->commandService->exec($this->getBaseSSHCommand($sshAdditionalParams) . ' "' . addslashes($command) . '"', $output, $silent, $tty);
        $this->logExecutionOnRemote($command, 'END');
        return $result;
    }

    /**
     * @param string $remotePath Absolute path to the source remote file
     * @param string $localPath Absolute path to target local file
     *
     * @return string
     */
    public function downloadFile($remotePath, $localPath)
    {
        $this->stopIfNotConnected();

        $cmdParts = array('scp');

        // port
        $cmdParts[] = '-P ' . $this->port;

        // user and host and path to source file path
        $cmdParts[] = $this->user . '@' . $this->host . ':' . $remotePath;

        // private key file
        if (!empty($this->privKeyFile)) {
            $cmdParts[] = '-i ' . $this->privKeyFile;
        }

        // target file path
        $cmdParts[] = $localPath;

        $cmd = implode(' ', $cmdParts);
        $output = $this->commandService->exec($cmd);

        return $output;
    }

    /**
     * @param string $localPath Absolute path to target local file
     * @param string $remotePath Absolute path to the source remote file
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return string
     */
    public function uploadFile($localPath, $remotePath, $output = null)
    {
        $this->stopIfNotConnected();

        $cmdParts = array('scp');

        // port
        $cmdParts[] = '-P ' . $this->port;

        // target file path
        $cmdParts[] = $localPath;

        // user and host and path to source file path
        $cmdParts[] = $this->user . '@' . $this->host . ':' . $remotePath;

        // private key file
        if (!empty($this->privKeyFile)) {
            $cmdParts[] = '-i ' . $this->privKeyFile;
        }

        $cmd = implode(' ', $cmdParts);
        $output = $this->commandService->exec($cmd, $output);

        return $output;
    }

    /**
     * Enables exec logs
     *
     * @return void
     */
    public function enableExecLog()
    {
        $this->enableExecLogs = TRUE;
    }

    /**
     * Disables exec logs
     *
     * @return void
     */
    public function disableExecLog()
    {
        $this->enableExecLogs = FALSE;
    }

    /**
     * Returns basic SSH command generated from connection settings
     *
     * @param $sshAdditionalParams
     * @return string
     */
    protected function getBaseSSHCommand($sshAdditionalParams = array())
    {
        // command
        $parts = array('ssh');

        // user and host
        $parts[] = $this->user . '@' . $this->host;

        // port
        $parts[] = '-p ' . $this->port;

        // private key file
        if (!empty($this->privKeyFile)) {
            $parts[] = '-i ' . $this->privKeyFile;
        }

        $parts = array_merge($parts, $sshAdditionalParams);

        return implode(' ', $parts);
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    protected function stopIfNotConnected()
    {
        if (!$this->isConnected()) {
            throw new \Exception('SSH connection is not initialized. Use SshService::connect() first.');
        }
    }

    /**
     * @return boolean
     */
    protected function isConnected()
    {
        return !empty($this->host) && !empty($this->user) && !empty($this->port);
    }

    /**
     * Saves exec logs on remote server
     *
     * @param $commandToLog
     * @param $label
     */
    protected function logExecutionOnRemote($commandToLog, $label)
    {
        if (!$this->isConnected()) {
            return;
        }
        $username = $this->getGitUsername();
        $log = date('Y-m-d H:i:s', time()) . "\t" . md5($commandToLog) . ':' . $label . "\t\t - Command executed by: \t[" . $username . "] - " . 'Executed command "' . $commandToLog . '"';
        $command = $this->getBaseSSHCommand() . ' "' . addslashes('echo ' . $log . ' >> deployment.log') . '"';
        $this->commandService->exec($command, null, $silent = 1);
    }

    /**
     * Return current GIT username
     *
     * @return mixed
     */
    protected function getGitUsername()
    {
        $output = array();
        exec('git config user.name', $output);

        return array_shift($output);
    }

    /**
     * @return string
     */
    public function getPrivKeyFile()
    {
        return $this->privKeyFile;
    }
}
