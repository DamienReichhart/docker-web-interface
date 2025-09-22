<?php /** @noinspection DuplicatedCode */

namespace App\Helper;

use Exception;
use InvalidArgumentException;
use RuntimeException;

class SshHelper extends Helper
{
    private mixed $connection;
    private string $host;
    private string $user;
    private ?string $pass;
    private int $port;
    private bool $sudo;
    private int $connectionTimeout = 10;
    private bool $connected = false;
    private array $connectionErrors = [];

    /**
     * Constructor to initialize the SSH connection details.
     * 
     * @param string $host The hostname or IP address to connect to
     * @param string $user The username for authentication
     * @param string|null $pass The password for authentication (optional if using key-based auth)
     * @param int $port The SSH port to connect to
     * @param bool $sudo Whether to use sudo for commands
     * @param int $timeout Connection timeout in seconds
     * 
     * @throws RuntimeException If the connection cannot be established
     */
    public function __construct(
        string $host,
        string $user,
        ?string $pass,
        int $port = 22,
        bool $sudo = false,
        int $timeout = 10
    ) {
        parent::__construct();
        
        if (empty($host)) {
            throw new InvalidArgumentException("Host cannot be empty");
        }
        
        if (empty($user)) {
            throw new InvalidArgumentException("User cannot be empty");
        }
        
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->sudo = $sudo;
        $this->connectionTimeout = $timeout;
        
        $this->connect();
        
        if ($this->connected) {
            $this->setSudo();
        }
    }

    /**
     * Destructor to close the SSH connection when the object is destroyed.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Disconnects from the SSH server.
     */
    public function disconnect(): void
    {
        if ($this->connection && is_resource($this->connection)) {
            $this->connected = false;
            // ssh2_disconnect is not reliable, can't really do much here
            $this->connection = null;
        }
    }

    /**
     * Connects to the SSH server using key-based authentication if possible,
     * otherwise falls back to password authentication.
     * 
     * @throws RuntimeException If authentication fails
     */
    private function connect(): void
    {
        if (!extension_loaded('ssh2')) {
            throw new RuntimeException("SSH2 extension is not loaded");
        }
        
        try {
            // Set connection timeout context options
            $context = stream_context_create([
                'ssh2' => [
                    'timeout' => $this->connectionTimeout,
                ]
            ]);
            
            // Connect with timeout
            $this->connection = @ssh2_connect($this->host, $this->port, []);
            
            if (!$this->connection) {
                $this->connectionErrors[] = "Failed to connect to {$this->host} on port {$this->port}";
                throw new RuntimeException("Failed to connect to {$this->host} on port {$this->port}");
            }
            
            $authenticated = $this->attemptAuthentication();
            
            if (!$authenticated) {
                $errorMessage = "Failed to authenticate with user {$this->user}";
                if (!empty($this->connectionErrors)) {
                    $errorMessage .= ": " . implode("; ", $this->connectionErrors);
                }
                throw new RuntimeException($errorMessage);
            }
            
            $this->connected = true;
        } catch (Exception $e) {
            $this->connectionErrors[] = $e->getMessage();
            throw new RuntimeException("SSH connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Attempts to authenticate with the SSH server using various methods
     * 
     * @return bool True if authentication succeeded
     */
    private function attemptAuthentication(): bool
    {
        $authenticated = false;

        // Attempt agent authentication
        if (function_exists('ssh2_auth_agent')) {
            try {
                if (@ssh2_auth_agent($this->connection, $this->user)) {
                    return true;
                }
            } catch (Exception $e) {
                $this->connectionErrors[] = "Agent auth failed: " . $e->getMessage();
            }
        }

        // Try key-based authentication with default keys
        if (!$authenticated) {
            $homeDir = getenv('HOME') ?: getenv('USERPROFILE'); // For Windows compatibility
            $keyPaths = [
                [$homeDir . '/.ssh/id_rsa.pub', $homeDir . '/.ssh/id_rsa'],
                [$homeDir . '/.ssh/id_ed25519.pub', $homeDir . '/.ssh/id_ed25519'],
                [$homeDir . '/.ssh/id_ecdsa.pub', $homeDir . '/.ssh/id_ecdsa'],
                [$homeDir . '/.ssh/id_dsa.pub', $homeDir . '/.ssh/id_dsa']
            ];
            
            foreach ($keyPaths as [$pubKey, $privKey]) {
                if (file_exists($privKey) && file_exists($pubKey)) {
                    try {
                        if (@ssh2_auth_pubkey_file($this->connection, $this->user, $pubKey, $privKey, '')) {
                            return true;
                        }
                    } catch (Exception $e) {
                        $this->connectionErrors[] = "Key auth failed for $privKey: " . $e->getMessage();
                    }
                }
            }
        }

        // If key-based authentication failed, try password authentication
        if (!$authenticated && $this->pass !== null) {
            try {
                if (@ssh2_auth_password($this->connection, $this->user, $this->pass)) {
                    return true;
                }
            } catch (Exception $e) {
                $this->connectionErrors[] = "Password auth failed: " . $e->getMessage();
            }
        }
        
        return false;
    }

    /**
     * Executes a command on the connected SSH server.
     *
     * @param string $command The command to execute on the server.
     * @return string The output of the command.
     * @throws RuntimeException If the command execution fails
     */
    final public function executeCommand(string $command): string
    {
        $this->ensureConnected();

        // Check if command starts with sudo and it's not already handled by our getSudoPrefix method
        if (strpos($command, 'sudo ') === 0 && empty($this->getSudoPrefix())) {
            // If we have a password and the command needs sudo, add the password injection
            if (!empty($this->pass)) {
                $command = "echo " . escapeshellarg($this->pass) . " | sudo -S " . substr($command, 5);
            }
        }

        // Prepare the command with our usual sudo prefix if needed
        $sudoPrefix = $this->getSudoPrefix();
        
        // If command already starts with sudo, don't add our prefix
        if (strpos($command, 'sudo ') === 0) {
            $fullCommand = $command . ' 2>&1';
        } else {
            $fullCommand = $sudoPrefix . $command . ' 2>&1';
        }

        // Execute the command
        $stream = @ssh2_exec($this->connection, $fullCommand);
        if (!$stream) {
            throw new RuntimeException("Failed to execute command: $command");
        }

        // Set up stderr stream
        $errorStream = @ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        if ($errorStream) {
            stream_set_blocking($errorStream, true);
        }

        // Enable blocking mode for the stream
        stream_set_blocking($stream, true);

        // Fetch the output of the command
        $output = stream_get_contents($stream);
        
        // Get any error output
        $errorOutput = "";
        if ($errorStream) {
            $errorOutput = stream_get_contents($errorStream);
            fclose($errorStream);
        }

        // Close the stream
        fclose($stream);

        // Remove sudo password prompt from the output, if applicable
        if ($this->sudo && !empty($this->pass)) {
            $output = str_replace("[sudo] password for {$this->user}:", '', $output);
        }

        // Combine stdout and stderr if there's error output
        if (!empty($errorOutput)) {
            $output .= "\n" . $errorOutput;
        }

        return nl2br($output);
    }

    /**
     * Executes a command with exact formatting on the connected SSH server.
     *
     * @param string $command The command to execute.
     * @return string The exact output of the command.
     * @throws RuntimeException If the command execution fails
     */
    final public function executeCommandWithExactFormatting(string $command): string
    {
        $this->ensureConnected();

        // Check if command starts with sudo and it's not already handled by our getSudoPrefix method
        if (strpos($command, 'sudo ') === 0 && empty($this->getSudoPrefix())) {
            // If we have a password and the command needs sudo, add the password injection
            if (!empty($this->pass)) {
                $command = "echo " . escapeshellarg($this->pass) . " | sudo -S " . substr($command, 5);
            }
        }

        // If command already starts with sudo, don't add our prefix
        if (strpos($command, 'sudo ') === 0) {
            $fullCommand = $command . ' 2>&1';
        } else {
            $sudoPrefix = $this->getSudoPrefix();
            $fullCommand = $sudoPrefix . $command . ' 2>&1';
        }

        // Execute the command
        $stream = @ssh2_exec($this->connection, $fullCommand);
        if (!$stream) {
            throw new RuntimeException("Failed to execute command: $command");
        }

        // Enable blocking mode for the stream
        stream_set_blocking($stream, true);

        // Fetch the output of the command
        $output = stream_get_contents($stream);

        // Close the stream
        fclose($stream);

        // Remove sudo password prompt from the output, if applicable
        if (($this->sudo || strpos($command, 'sudo ') === 0) && !empty($this->pass)) {
            $output = str_replace("[sudo] password for {$this->user}:", '', $output);
        }

        // Return the output as-is
        return $output;
    }

    /**
     * Executes a command in the background on the connected SSH server.
     *
     * @param string $command The command to execute in the background.
     * @return string The command output or status message.
     * @throws RuntimeException If the command execution fails
     */
    final public function executeCommandInBackground(string $command): string
    {
        $this->ensureConnected();

        $sudoPrefix = $this->getSudoPrefix();
        
        // Properly format the command to run in background with nohup
        $bgCommand = "nohup " . $sudoPrefix . $command . " > /tmp/bg_command.log 2>&1 & echo $!";
        
        // Execute the command to start background process
        $stream = @ssh2_exec($this->connection, $bgCommand);
        if (!$stream) {
            throw new RuntimeException("Failed to execute background command: $command");
        }
        
        // Enable blocking to get the PID
        stream_set_blocking($stream, true);
        $pid = trim(stream_get_contents($stream));
        fclose($stream);
        
        if (is_numeric($pid)) {
            return "Command executed in background (PID: $pid): $command";
        } else {
            return "Command started in background: $command";
        }
    }

    /**
     * Tests and sets the sudo capability for this connection
     */
    private function setSudo(): void
    {
        if (!$this->connected) {
            return;
        }

        // If sudo is not required, skip testing
        if (!$this->sudo) {
            return;
        }
        
        try {
            // Test if we can execute a simple command without sudo
            $testCmd = "docker ps -a";
            $stream = @ssh2_exec($this->connection, $testCmd . ' 2>&1');
            if (!$stream) {
                $this->sudo = true;
                return;
            }
            
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            fclose($stream);
            
            // If we get permission denied, we need sudo
            if (str_contains($output, 'permission denied') || str_contains($output, 'Permission denied')) {
                $this->sudo = true;
                
                // Test sudo access
                $sudoTest = $this->getSudoPrefix() . $testCmd;
                $stream = @ssh2_exec($this->connection, $sudoTest . ' 2>&1');
                if (!$stream) {
                    // Even sudo failed
                    throw new RuntimeException("Failed to execute even with sudo. Check sudo privileges.");
                }
                
                stream_set_blocking($stream, true);
                $sudoOutput = stream_get_contents($stream);
                fclose($stream);
                
                if (str_contains($sudoOutput, 'password') && empty($this->pass)) {
                    throw new RuntimeException("Sudo requires password but none provided");
                }
            } else {
                // No permission issues, we don't need sudo
                $this->sudo = false;
            }
        } catch (Exception $e) {
            $this->connectionErrors[] = "Sudo test failed: " . $e->getMessage();
            // Default to requiring sudo if we can't determine
            $this->sudo = true;
        }
    }

    /**
     * Sends a file from the local filesystem to the remote server via SCP.
     *
     * @param string $localPath  The full (local) path of the file to send.
     * @param string $remotePath The remote path where the file should be placed.
     *
     * @throws RuntimeException If the file transfer fails
     */
    public function sendFile(string $localPath, string $remotePath): void
    {
        $this->ensureConnected();

        if (!file_exists($localPath)) {
            throw new RuntimeException("Local file does not exist: $localPath");
        }

        // Get remote directory
        $remoteDir = dirname($remotePath);
        
        // Make sure the remote directory exists
        try {
            $this->executeCommand("mkdir -p " . escapeshellarg($remoteDir));
        } catch (Exception $e) {
            throw new RuntimeException("Failed to create remote directory: " . $e->getMessage());
        }
        
        // Send the file
        if (!@ssh2_scp_send($this->connection, $localPath, $remotePath, 0644)) {
            throw new RuntimeException("Failed to send file from '$localPath' to '$remotePath'");
        }
    }
    
    /**
     * Retrieves a file from the remote server to the local filesystem via SCP.
     *
     * @param string $remotePath The path of the remote file to download.
     * @param string $localPath  The local path where the file should be saved.
     *
     * @throws RuntimeException If the file transfer fails
     */
    public function getFile(string $remotePath, string $localPath): void
    {
        $this->ensureConnected();
        
        // Make sure the local directory exists
        $localDir = dirname($localPath);
        if (!is_dir($localDir) && !mkdir($localDir, 0755, true)) {
            throw new RuntimeException("Failed to create local directory: $localDir");
        }
        
        // Download the file
        if (!@ssh2_scp_recv($this->connection, $remotePath, $localPath)) {
            throw new RuntimeException("Failed to download file from '$remotePath' to '$localPath'");
        }
    }
    
    /**
     * Makes sure we're connected before executing commands
     * 
     * @throws RuntimeException If not connected
     */
    private function ensureConnected(): void
    {
        if (!$this->connected || !$this->connection) {
            try {
                $this->connect();
            } catch (Exception $e) {
                throw new RuntimeException("Not connected to SSH server: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Returns the appropriate sudo prefix for commands
     * 
     * @return string The sudo command prefix or empty string
     */
    private function getSudoPrefix(): string
    {
        if (!$this->sudo) {
            return '';
        }
        
        if (!empty($this->pass)) {
            // Use printf to avoid leaving password visible in process list
            return "echo " . escapeshellarg($this->pass) . " | sudo -S ";
        } else {
            return "sudo -n ";
        }
    }
    
    /**
     * Checks if the connection is active
     * 
     * @return bool Whether we're connected
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->connection !== null;
    }
    
    /**
     * Gets connection errors that occurred during connection attempts
     * 
     * @return array List of error messages
     */
    public function getConnectionErrors(): array
    {
        return $this->connectionErrors;
    }
    
    /**
     * Execute multiple commands in sequence
     * 
     * @param array $commands List of commands to execute
     * @return array Associative array of command => output pairs
     */
    public function executeMultipleCommands(array $commands): array
    {
        $results = [];
        
        foreach ($commands as $command) {
            try {
                $results[$command] = $this->executeCommand($command);
            } catch (Exception $e) {
                $results[$command] = "ERROR: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Tests if a remote path exists
     * 
     * @param string $path Remote path to check
     * @return bool Whether the path exists
     */
    public function pathExists(string $path): bool
    {
        try {
            $result = $this->executeCommand("test -e " . escapeshellarg($path) . " && echo 'exists' || echo 'not exists'");
            return strpos($result, 'exists') !== false;
        } catch (Exception) {
            return false;
        }
    }
    
    /**
     * Creates a directory on the remote server
     * 
     * @param string $path Directory path to create
     * @param bool $recursive Whether to create parent directories if needed
     * @return bool Success or failure
     */
    public function createDirectory(string $path, bool $recursive = true): bool
    {
        try {
            $mkdirCmd = $recursive ? "mkdir -p" : "mkdir";
            $this->executeCommand("$mkdirCmd " . escapeshellarg($path));
            return true;
        } catch (Exception) {
            return false;
        }
    }
}
