<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedFieldInspection */

namespace App\Entity\Docker;

use App\Enum\DockerContainerStatusEnum;
use App\Helper\DockerHelper;
use DateTimeImmutable;
use Exception;
use JsonException;
use RuntimeException;

class DockerSingleContainer
{
    private string $id;
    private string $name;
    private DockerSingleImage $image;
    private DockerContainerStatusEnum $status;
    private DateTimeImmutable $createdAt;
    private DockerContainerEnv $env;
    private DockerContainerLogs $logs;
    private DockerContainerStats $stats;
    private ?DockerContainerPorts $ports = null;
    private DockerContainerVolumes $volumes;
    private DockerContainerCommand $command;
    private DockerSingleContainerInspect $inspect;
    private ?string $rawStatus = null;
    private ?string $rawName = null;
    private ?string $rawImage = null;
    private ?string $rawPorts = null;

    final public function __construct(string $id)
    {
        $this->id = $id;
    }

    private function assertInspect(): void
    {
        if (!isset($this->inspect)) {
            try {
                $this->inspect = new DockerSingleContainerInspect($this->id);
            } catch (Exception $e) {
                // Handle inspect failure with a more specific error
                throw new RuntimeException("Failed to inspect container '{$this->id}': " . $e->getMessage());
            }
        }
    }

    /**
     * Sets the raw status string from docker ps output
     * 
     * @param string $status Raw status string from docker ps output
     */
    final public function setRawStatus(string $status): void
    {
        $this->rawStatus = $status;
    }

    /**
     * Gets the raw status string if available
     */
    final public function getRawStatus(): ?string
    {
        return $this->rawStatus;
    }

    final public function getId(): string
    {
        return $this->id;
    }
    final public function getImage(): DockerSingleImage
    {
        // If we can't load the proper image object but have raw image string, create a simple object
        if (!isset($this->image)) {
            try {
                $this->assertInspect();
                $this->image = new DockerSingleImage($this->inspect->Config->Image);
            } catch (Exception $e) {
                // If inspect fails but we have raw image name, use that
                if ($this->rawImage !== null) {
                    $this->image = new DockerSingleImage($this->rawImage);
                } else {
                    // Last resort - create a placeholder image
                    $this->image = new DockerSingleImage("unknown");
                }
            }
        }
        return $this->image;
    }

    final public function getCommand(): DockerContainerCommand
    {
        $this->assertInspect();
        if (!isset($this->command)) {
            $this->command = new DockerContainerCommand($this->inspect->Config->Cmd);
        }
        return $this->command;
    }

    final public function getInspect(): DockerSingleContainerInspect
    {
        $this->assertInspect();

        return $this->inspect;
    }

    final public function getLogs(): DockerContainerLogs
    {
        if (!isset($this->logs)) {
            $this->logs = new DockerContainerLogs($this->id);
        }
        return $this->logs;
    }

    final public function getStats(): DockerContainerStats
    {
        if (!isset($this->stats)) {
            $this->stats = new DockerContainerStats($this->id);
        }
        return $this->stats;
    }

    /**
     * Gets ports as a string, trying to use raw data if inspect fails
     */
    final public function getPorts(): string 
    {
        // If we have raw ports data, use it
        if ($this->rawPorts !== null) {
            return $this->rawPorts;
        }
        
        // Otherwise, try to use the detailed ports object
        try {
            $portsObj = $this->getPortsObject();
            $portsArray = $portsObj->getPorts();
            
            if (empty($portsArray)) {
                return "";
            }
            
            $result = [];
            foreach ($portsArray as $port) {
                $result[] = $port->toPortMap();
            }
            
            return implode(", ", $result);
        } catch (Exception $e) {
            return "";
        }
    }
    
    /**
     * Gets the detailed ports object with mapping information
     *
     * @throws JsonException
     */
    final public function getPortsObject(): DockerContainerPorts
    {
        $this->assertInspect();

        if (!isset($this->ports)) {
            $portBindings = $this->inspect->HostConfig->PortBindings ?? [];

            if (empty($portBindings)) {
                $portBindings = $this->inspect->NetworkSettings->Ports ?? [];
            }

            if (empty($portBindings)) {
                $this->ports = new DockerContainerPorts([]);
            } else {
                $this->ports = DockerContainerPorts::fromStdClass($portBindings);
            }
        }

        return $this->ports;
    }

    final public function getVolumes(): DockerContainerVolumes
    {
        $this->assertInspect();
        if (isset($this->volumes)) {
            return $this->volumes;
        }
        if ($this->inspect->Mounts === []){
            $this->volumes = new DockerContainerVolumes($this->inspect->Mounts);
        } else {
            $this->volumes = DockerContainerVolumes::fromStdClass($this->inspect->Mounts);
        }
        return $this->volumes;
    }

    final public function getStatus(): DockerContainerStatusEnum
    {
        if (isset($this->status)) {
            return $this->status;
        }

        try {
            // Try to get status from Docker inspect
            $this->assertInspect();
            
            // Ensure we can access the State.Status property
            if (isset($this->inspect->State) && isset($this->inspect->State->Status)) {
                $dockerStatus = strtolower($this->inspect->State->Status);
                $this->status = $this->mapDockerStatusToEnum($dockerStatus);
                return $this->status;
            }
            
            throw new RuntimeException("Container inspect data is missing Status field");
        } catch (Exception $e) {
            // If inspect fails, use raw status from docker ps if available
            if ($this->rawStatus !== null) {
                // Map docker ps status strings to our enum
                $this->status = $this->mapRawStatusToEnum($this->rawStatus);
            } else {
                // Default to ERROR if we can't determine the status
                $this->status = DockerContainerStatusEnum::ERROR;
            }
        }

        return $this->status;
    }

    /**
     * Maps a Docker API status string to the appropriate enum value
     */
    private function mapDockerStatusToEnum(string $status): DockerContainerStatusEnum
    {
        return $this->mapRawStatusToEnum($status);
    }

    /**
     * Maps a raw status string from docker ps to the appropriate enum value
     */
    private function mapRawStatusToEnum(string $rawStatus): DockerContainerStatusEnum
    {
        $rawStatus = strtolower($rawStatus);
    

        // Handle English status strings
        if (str_contains($rawStatus, 'up') || str_contains($rawStatus, 'running')) {
            return DockerContainerStatusEnum::RUNNING;
        } elseif (str_contains($rawStatus, 'creat')) {
            return DockerContainerStatusEnum::CREATED;
        } elseif (str_contains($rawStatus, 'start') || str_contains($rawStatus, 'restart')) {
            return DockerContainerStatusEnum::STARTING;
        } elseif (str_contains($rawStatus, 'exit')) {
            return DockerContainerStatusEnum::EXITED;
        } elseif (str_contains($rawStatus, 'stop') || str_contains($rawStatus, 'pause')) {
            return DockerContainerStatusEnum::STOPPED;
        }
        
        return DockerContainerStatusEnum::ERROR;
    }

    final public function getName(): string
    {
        // If we already have the raw name from docker ps, use it
        if ($this->rawName !== null) {
            return $this->rawName;
        }
        
        try {
            $this->assertInspect();

            if (!isset($this->name)) {
                // Remove the leading slash from Docker container names
                $this->name = ltrim($this->inspect->Name, '/');
                
                // If name is still empty, use container ID as fallback
                if (empty($this->name)) {
                    $this->name = "container_" . substr($this->id, 0, 12);
                }
            }

            return $this->name;
        } catch (Exception $e) {
            // Use container ID as fallback if we can't get the name
            return "container_" . substr($this->id, 0, 12);
        }
    }

    /**
     * @throws Exception
     */
    final public function getCreatedAt(): DateTimeImmutable
    {
        $this->assertInspect();

        if (!isset($this->createdAt)) {
            $this->createdAt = new DateTimeImmutable($this->inspect->Created);
        }

        return $this->createdAt;
    }

    /** @noinspection PhpConditionAlreadyCheckedInspection */
    final public function getEnvs(): DockerContainerEnv
    {
        $this->assertInspect();
        if (isset($this->env)) {
            return $this->env;
        }

        if (!isset($this->env)) {
            $this->env = new DockerContainerEnv($this->inspect->Config->Env);
        }

        return $this->env;
    }

    final public function getEnv(): DockerContainerEnv
    {
        return $this->getEnvs();
    }

    final public function start(): void
    {
        $helper = DockerHelper::getInstance();

        $helper->startContainer($this->id);
    }

    final public function stop(): void
    {
        $helper = DockerHelper::getInstance();

        $helper->stopContainer($this->id);
    }

    /**
     * @throws JsonException
     */
    final public function addPort(string $externalPort, string $internalPort): void
    {
        $this->assertInspect();
        if ($this->inspect->NetworkSettings->Ports === []) {
            $this->ports = new DockerContainerPorts($this->inspect->NetworkSettings->Ports);
        } else {
            $this->ports = DockerContainerPorts::fromStdClass($this->inspect->NetworkSettings->Ports);
        }
        $this->ports->addPort($externalPort, $internalPort);
    }

    final public function setCommand(mixed $command): void
    {
        $this->command = new DockerContainerCommand([$command,]);
    }

    /**
     * @throws JsonException
     */
    final public function removePortByIndex(int $index): void
    {
        $this->assertInspect();
        if ($this->inspect->NetworkSettings->Ports === []) {
            $this->ports = new DockerContainerPorts($this->inspect->NetworkSettings->Ports);
        } else {
            $this->ports = DockerContainerPorts::fromStdClass($this->inspect->NetworkSettings->Ports);
        }
        $this->ports->removePortByIndex($index);
    }

    final public function addVolume(mixed $sourceVolume, mixed $targetVolume): void
    {
        $this->assertInspect();
        if ($this->inspect->Mounts === []) {
            $this->volumes = new DockerContainerVolumes($this->inspect->Mounts);
        } else {
            $this->volumes = DockerContainerVolumes::fromStdClass($this->inspect->Mounts);
        }
        $this->volumes->addVolume($sourceVolume, $targetVolume);
    }

    final public function addEnv(mixed $envName, mixed $envValue): void
    {
        $this->assertInspect();
        $this->env = new DockerContainerEnv($this->inspect->Config->Env);
        $this->env->addEnv($envName, $envValue);
    }

    final public function assertStatus(): void
    {
        $this->getStatus();
    }

    /**
     * Sets the raw name from docker ps output
     */
    final public function setRawName(string $name): void
    {
        $this->rawName = $name;
    }

    /**
     * Sets the raw image from docker ps output
     */
    final public function setRawImage(string $image): void
    {
        $this->rawImage = $image;
    }

    /**
     * Sets the raw ports from docker ps output
     * 
     * @param string $ports Raw ports string from docker ps output
     */
    final public function setRawPorts(string $ports): void
    {
        $this->rawPorts = $ports;
        // Reset any cached ports object since we've updated the raw data
        $this->ports = null;
    }
}