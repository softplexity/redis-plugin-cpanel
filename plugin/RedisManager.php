<?php

class RedisManager
{
    private $cpanel;

    public $username;
    public $userdetails;

    /**
     * Constructor to initialize the RedisManager with a CPANEL object.
     *
     * @param CPANEL $cpanel
     */
    public function __construct($cpanel)
    {
        $this->cpanel = $cpanel;
        $this->username = $this->getUser();
        $this->userdetails = $this->getUserDetails();
    }

    /**
     * Starts the Redis service for the user.
     *
     * @throws Exception if the Redis service fails to start.
     */
    public function startRedis()
    {
        $command = $this->executeCommand("redis-server --daemonize yes");
        if (strpos($command, 'OK') === false) {
            throw new Exception("Failed to start Redis server. Response: $command");
        }
    }

    /**
     * Stops the Redis service for the user.
     *
     * @throws Exception if the Redis service fails to stop.
     */
    public function stopRedis()
    {
        $command = $this->executeCommand("redis-cli shutdown");
        if (strpos($command, 'not connected') !== false) {
            throw new Exception("Failed to stop Redis server. Response: $command");
        }
    }

    /**
     * Checks the status of the Redis service for the user.
     *
     * Outputs the service status to stdout.
     */
    public function checkRedisStatus()
    {
        $command = $this->executeCommand("redis-cli info");
        if (empty($command) || strpos($command, 'redis_version') === false) {
            echo "Not Running (Stopped)";
            return;
        }

        $info = $this->parseRedisInfo($command);

        echo "Running {$info['port']} {$info['requirepass']} {$info['maxmemory']} {$info['databases']}";
    }

    /**
     * Gets the current user's username.
     *
     * @return string User's username.
     */
    private function getUser()
    {
        $response = $this->cpanel->uapi('UserManager', 'get_current_user');
        return $response['cpanelresult']['result']['data']['user'] ?? 'unknown';
    }

    /**
     * Fetches details about the current user.
     *
     * @return array Associative array with user details.
     */
    private function getUserDetails()
    {
        $response = $this->cpanel->uapi('Mysql', 'get_restriction_info');
        return $response['cpanelresult']['result']['data'] ?? [];
    }

    /**
     * Executes a system command and returns the result.
     *
     * @param string $command The command to execute.
     * @return string The output of the command.
     */
    private function executeCommand($command)
    {
        return shell_exec($command);
    }

    /**
     * Parses the output of the `redis-cli info` command into an associative array.
     *
     * @param string $infoOutput The raw output from `redis-cli info`.
     * @return array Associative array of parsed Redis details.
     */
    private function parseRedisInfo($infoOutput)
    {
        $lines = explode("\n", $infoOutput);
        $info = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(":", $line, 2);
                $info[trim($key)] = trim($value);
            }
        }
        return [
            'port' => $info['tcp_port'] ?? 'N/A',
            'maxmemory' => $info['maxmemory'] ?? 'N/A',
            'requirepass' => $info['requirepass'] ?? 'N/A',
            'databases' => $info['databases'] ?? 'N/A',
        ];
    }
}