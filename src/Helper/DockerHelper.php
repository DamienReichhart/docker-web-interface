<?php /** @noinspection PhpUnused */

namespace App\Helper;

use App\Entity\Docker\DockerContainerCommand;
use App\Entity\Docker\DockerContainerEnv;
use App\Entity\Docker\DockerContainerPorts;
use App\Entity\Docker\DockerContainers;
use App\Entity\Docker\DockerContainerVolumes;
use App\Entity\Docker\DockerSingleContainer;
use App\Entity\Docker\DockerSingleImage;
use Exception;
use JsonException;
use RuntimeException;

class DockerHelper extends Helper
{
    private SshHelper $sshHelper;
    private static ?DockerHelper $instance = null;
    private bool $composeSupported = false;

    private function __construct(SshHelper $sshHelper)
    {
        parent::__construct();
        $this->sshHelper = $sshHelper;
        $this->checkComposeSupport();
    }

    final public static function getInstance(?SshHelper $sshHelper = null): DockerHelper
    {
        if (self::$instance === null && $sshHelper !== null) {
            self::$instance = new DockerHelper($sshHelper);
        } elseif (self::$instance === null && $sshHelper === null) {
            throw new RuntimeException("SSH Helper must be provided when initializing DockerHelper");
        }
        return self::$instance;
    }

    /**
     * Checks if Docker Compose is supported on the remote system
     */
    private function checkComposeSupport(): void
    {
        try {
            $result = $this->sshHelper->executeCommand('docker compose version 2>/dev/null || docker-compose version 2>/dev/null');
            $this->composeSupported = !empty($result) && !str_contains($result, 'not found');
        } catch (Exception) {
            $this->composeSupported = false;
        }
    }

    /**
     * Retrieves all containers from the Docker host
     */
    final public function getContainers(): DockerContainers
    {
        try {
            // Force English locale for consistent output format
            $command = 'LC_ALL=C docker ps -a --format "{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}\t{{.Command}}"';
            $output = $this->sshHelper->executeCommand($command);
            return new DockerContainers($output);
        } catch (Exception $e) {
            // If the error is permission denied, try with sudo
            if (stripos($e->getMessage(), 'permission denied') !== false) {
                // Force English locale for consistent output format with sudo
                $command = 'LC_ALL=C sudo docker ps -a --format "{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}\t{{.Command}}"';
                $output = $this->sshHelper->executeCommand($command);
                return new DockerContainers($output);
            }
            throw $e;
        }
    }

    /**
     * Gets detailed information about a specific container
     */
    final public function inspectContainer(string $containerId): string
    {
        try {
            // First try without sudo, force English locale
            $command = "LC_ALL=C docker inspect " . escapeshellarg($containerId);
            $output = $this->sshHelper->executeCommandWithExactFormatting($command);
            
            // Simple validation to ensure we have JSON output
            if (empty($output) || (strpos($output, '{') === false && strpos($output, '[') === false)) {
                // If output suggests permission error, try with sudo
                if (stripos($output, 'permission denied') !== false) {
                    // Force English locale with sudo
                    $command = "LC_ALL=C sudo docker inspect " . escapeshellarg($containerId);
                    $output = $this->sshHelper->executeCommandWithExactFormatting($command);
                    
                    // Check if we have valid output now
                    if (empty($output) || (strpos($output, '{') === false && strpos($output, '[') === false)) {
                        throw new RuntimeException("Invalid output from Docker inspect command even with sudo: " . substr($output, 0, 100));
                    }
                } else {
                    throw new RuntimeException("Invalid output from Docker inspect command: " . substr($output, 0, 100));
                }
            }
            
            return $output;
        } catch (Exception $e) {
            // If the error is permission denied, try with sudo
            if (stripos($e->getMessage(), 'permission denied') !== false) {
                try {
                    // Force English locale with sudo
                    $command = "LC_ALL=C sudo docker inspect " . escapeshellarg($containerId);
                    return $this->sshHelper->executeCommandWithExactFormatting($command);
                } catch (Exception $sudoEx) {
                    throw new RuntimeException("Permission denied accessing Docker. Sudo attempt also failed: " . $sudoEx->getMessage());
                }
            }
            
            throw new RuntimeException("Error inspecting container: " . $e->getMessage());
        }
    }

    /**
     * Gets detailed information about a specific image
     */
    final public function inspectImage(string $imageId): string
    {
        try {
            $command = "docker image inspect " . escapeshellarg($imageId);
            $output = $this->sshHelper->executeCommandWithExactFormatting($command);
            
            // Check if we need to try with sudo
            if (stripos($output, 'permission denied') !== false) {
                $command = "sudo docker image inspect " . escapeshellarg($imageId);
                $output = $this->sshHelper->executeCommandWithExactFormatting($command);
            }
            
            return $output;
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'permission denied') !== false) {
                try {
                    $command = "sudo docker image inspect " . escapeshellarg($imageId);
                    return $this->sshHelper->executeCommandWithExactFormatting($command);
                } catch (Exception $sudoEx) {
                    throw $sudoEx;
                }
            }
            throw $e;
        }
    }

    /**
     * Runs a new container with the specified configuration
     */
    final public function runNewContainer(
        DockerSingleImage $image, 
        string $name, 
        ?DockerContainerPorts $ports = null, 
        ?DockerContainerVolumes $volumes = null, 
        ?DockerContainerEnv $envs = null, 
        ?DockerContainerCommand $command = null,
        bool $restartAlways = false,
        ?string $healthCheck = null
    ): string {
        $imageName = $image->getRepoWithTag();
        $usedPorts = $ports !== null ? $ports->getPorts() : [];
        $usedVolumes = $volumes !== null ? $volumes->getVolumes() : [];
        $usedEnvs = $envs !== null ? $envs->getEnvs() : [];
        $commandString = $command !== null ? $command->getCommandString() : '';

        $cmd = 'docker run -d';
        
        if ($restartAlways) {
            $cmd .= ' --restart always';
        }
        
        $cmd .= ' --name ' . escapeshellarg($name);

        foreach ($usedPorts as $port) {
            $cmd .= ' -p ' . $port->toPortMap();
        }
        
        foreach ($usedVolumes as $volume) {
            $cmd .= ' -v ' . $volume->toVolumeMap();
        }
        
        foreach ($usedEnvs as $env) {
            $cmd .= ' -e ' . $env->toEnvString();
        }
        
        if ($healthCheck) {
            $cmd .= ' --health-cmd=' . escapeshellarg($healthCheck);
            $cmd .= ' --health-interval=30s';
            $cmd .= ' --health-timeout=10s';
            $cmd .= ' --health-retries=3';
        }
        
        $cmd .= ' ' . $imageName;
        
        if ($commandString !== '') {
            $cmd .= ' ' . $commandString;
        }

        return $this->sshHelper->executeCommand($cmd);
    }

    /**
     * Starts a container by ID
     */
    final public function startContainer(string $containerId): string
    {
        $command = 'LC_ALL=C docker start ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Stops a container by ID
     */
    final public function stopContainer(string $containerId): string
    {
        $command = 'LC_ALL=C docker stop ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Restarts a container by ID
     */
    final public function restartContainer(string $containerId): string
    {
        $command = 'LC_ALL=C docker restart ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Runs an image directly with configuration parameters
     */
    final public function runImage(string $image, string $name, array $env=[], array $volumes=[], array $ports=[], ?string $entryCommand = null): string
    {
        $command = 'docker run ';
        
        foreach ($env as $value) {
            $command .= ' -e ' . escapeshellarg($value);
        }
        
        foreach ($volumes as $value) {
            $command .= ' -v ' . escapeshellarg($value);
        }
        
        foreach ($ports as $value) {
            $command .= ' -p ' . escapeshellarg($value);
        }
        
        if ($entryCommand !== null) {
            $command .= ' --entrypoint ' . escapeshellarg($entryCommand);
        }
        
        $command .= ' --name ' . escapeshellarg($name) . ' -d -i ' . escapeshellarg($image);
        $output = $this->sshHelper->executeCommand($command);

        if (str_contains($output, 'Error response from daemon')) {
            return "Failed to run image: $output";
        }
        
        return '';
    }

    /**
     * Retrieves environment variables for a container
     */
    final public function getContainerEnv(string $containerId): string
    {
        $command = 'docker inspect --format=\'{{range .Config.Env}}{{println .}}{{end}}\' ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Retrieves all Docker images
     */
    final public function getImages(): string
    {
        try {
            $command = 'docker images --format "{{.ID}}\t{{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"';
            $output = $this->sshHelper->executeCommand($command);
            return $output;
        } catch (Exception $e) {
            // If the error is permission denied, try with sudo
            if (stripos($e->getMessage(), 'permission denied') !== false) {
                $command = 'sudo docker images --format "{{.ID}}\t{{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"';
                $output = $this->sshHelper->executeCommand($command);
                return $output;
            }
            throw $e;
        }
    }

    /**
     * Pulls an image from a Docker registry
     */
    final public function pullImage(string $repoWithTag): string
    {
        $command = 'docker pull ' . str_replace("'", '', escapeshellarg($repoWithTag));
        return $this->sshHelper->executeCommandInBackground($command);
    }

    /**
     * Gets logs for a specific container
     */
    final public function getContainerLogs(string $containerId, int $lines = 100): string
    {
        $command = 'docker logs --tail=' . $lines . ' ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Follows logs for a container in real-time
     */
    final public function followContainerLogs(string $containerId): string
    {
        $command = 'docker logs -f ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommandInBackground($command);
    }

    /**
     * Retrieves performance statistics for a container
     */
    final public function getContainerStats(string $containerId): string
    {
        $command = 'docker stats --no-stream --format "{{.Container}}\t{{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}" ' . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Deletes a container by ID
     */
    final public function deleteContainer(string $containerId, bool $force = false): string
    {
        try {
            $this->stopContainer($containerId);
        } catch (Exception) {
            // Container might already be stopped, continue with removal
        }
        
        $command = 'docker rm ' . ($force ? '-f ' : '') . escapeshellarg($containerId);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Gets container details by ID
     */
    final public function getContainer(string $containerId): string
    {
        $command = 'docker ps -a --filter "id=' . escapeshellarg($containerId) . '" --format "{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}\t{{.Command}}"';
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Gets container health status
     */
    final public function getContainerHealth(string $containerId): string
    {
        $command = 'docker inspect --format="{{json .State.Health}}" ' . escapeshellarg($containerId) . ' 2>/dev/null || echo "no health check"';
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Re-creates a container with updated settings
     * @throws JsonException
     */
    final public function reRunContainer(DockerSingleContainer $container, bool $restartAlways = false, ?string $healthCheck = null): void
    {
        $name = $container->getName();
        $image = $container->getImage();
        
        try {
            // Get ports object, not string representation
            $ports = $container->getPortsObject();
            // Ensure we have a valid ports object
            if ($ports === null) {
                $ports = new DockerContainerPorts([]);
            }
        } catch (Exception $e) {
            // If getting ports fails, create an empty ports object
            $ports = new DockerContainerPorts([]);
            error_log("Error getting container ports: " . $e->getMessage());
        }
        
        $volumes = $container->getVolumes();
        $envs = $container->getEnvs();
        $command = $container->getCommand();

        // Stop and remove the existing container (if it exists)
        try {
            $this->deleteContainer($container->getId());
        } catch (Exception $e) {
            // If deletion fails, log it but continue with creating the new container
            error_log("Failed to delete container before recreating: " . $e->getMessage());
        }

        // Run a new container with the same name and modified settings
        $this->runNewContainer(
            $image,
            $name,
            $ports,
            $volumes,
            $envs,
            $command,
            $restartAlways,
            $healthCheck
        );
    }

    /**
     * Builds a custom Docker image from a Dockerfile
     * 
     * @param string $imagePath Path to the Dockerfile on the remote server
     * @param string $name Name to tag the image with
     * @param array $buildArgs Build arguments to pass to docker build
     * @return string Command output or error message
     */
    final public function buildCustomImage(string $imagePath, string $name, array $buildArgs = []): string
    {
        // Create a dedicated build context directory on the remote server
        $contextDir = dirname($imagePath);
        $dockerfileName = basename($imagePath);
        
        // Ensure the build command specifies the Dockerfile properly
        $command = 'docker build -f ' . escapeshellarg($imagePath) . ' -t ' . escapeshellarg($name) . ':latest';
        
        foreach ($buildArgs as $key => $value) {
            $command .= ' --build-arg ' . escapeshellarg("$key=$value");
        }
        
        // Send the command output to a log file for debugging
        $logFile = '/tmp/docker_build_' . $name . '.log';
        $command .= ' ' . escapeshellarg($contextDir) . ' 2>&1 | tee ' . escapeshellarg($logFile);
        
        try {
            $result = $this->sshHelper->executeCommand($command);
            return is_string($result) ? $result : "Command executed but returned non-string output";
        } catch (Exception $e) {
            // Try reading the log file to get more detailed error information
            try {
                $errorLog = $this->sshHelper->executeCommand('cat ' . escapeshellarg($logFile) . ' 2>/dev/null || echo "No log available"');
                return "Error building image: " . $e->getMessage() . "\nBuild log:\n" . 
                       (is_string($errorLog) ? $errorLog : "Could not read error log");
            } catch (Exception) {
                return "Error building image: " . $e->getMessage();
            }
        }
    }

    /**
     * Checks if Docker Compose is supported
     */
    final public function isComposeSupported(): bool
    {
        return $this->composeSupported;
    }

    /**
     * Gets the Docker Compose command
     */
    private function getComposeCommand(): string
    {
        $composeCommand = $this->sshHelper->executeCommand('command -v docker-compose || command -v docker compose');
        
        if (str_contains($composeCommand, 'docker compose')) {
            return 'docker compose';
        }
        
        return 'docker-compose';
    }

    /**
     * Deploy a stack using Docker Compose
     */
    final public function deployCompose(string $composePath, string $projectName): string
    {
        if (!$this->composeSupported) {
            throw new RuntimeException("Docker Compose is not supported on this server");
        }
        
        $composeCmd = $this->getComposeCommand();
        $command = "$composeCmd -f " . escapeshellarg($composePath) . " -p " . escapeshellarg($projectName) . " up -d";
        return $this->sshHelper->executeCommandInBackground($command);
    }

    /**
     * Stops and removes a Docker Compose stack
     */
    final public function removeCompose(string $composePath, string $projectName): string
    {
        if (!$this->composeSupported) {
            throw new RuntimeException("Docker Compose is not supported on this server");
        }
        
        $composeCmd = $this->getComposeCommand();
        $command = "$composeCmd -f " . escapeshellarg($composePath) . " -p " . escapeshellarg($projectName) . " down";
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Lists all Docker networks
     */
    final public function getNetworks(): string
    {
        $command = 'docker network ls --format "{{.ID}}\t{{.Name}}\t{{.Driver}}\t{{.Scope}}"';
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Creates a new Docker network
     */
    final public function createNetwork(string $name, string $driver = 'bridge', array $options = []): string
    {
        $command = 'docker network create --driver=' . escapeshellarg($driver);
        
        foreach ($options as $key => $value) {
            $command .= ' --opt ' . escapeshellarg("$key=$value");
        }
        
        $command .= ' ' . escapeshellarg($name);
        return $this->sshHelper->executeCommand($command);
    }

    /**
     * Prunes unused Docker resources
     */
    final public function pruneResources(bool $volumes = false, bool $images = false): string
    {
        $output = "";
        
        // Prune containers
        $output .= $this->sshHelper->executeCommand('docker container prune -f') . "\n";
        
        // Prune networks
        $output .= $this->sshHelper->executeCommand('docker network prune -f') . "\n";
        
        if ($volumes) {
            $output .= $this->sshHelper->executeCommand('docker volume prune -f') . "\n";
        }
        
        if ($images) {
            $output .= $this->sshHelper->executeCommand('docker image prune -a -f') . "\n";
        }
        
        return $output;
    }
}
