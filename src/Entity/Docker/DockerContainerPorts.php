<?php

namespace App\Entity\Docker;

use Exception;
use JsonException;
use RuntimeException;
use stdClass;

class DockerContainerPorts
{
    /**
     * @var DockerSingleContainerPort[]
     */
    private array $ports = [];

    final public function __construct(array $ports = [])
    {
        foreach ($ports as $containerPort => $bindings) {
            if ($bindings === null) {
                continue;
            }
            foreach ($bindings as $binding) {
                $hostPort = $binding['HostPort'] ?? '';
                $containerPortNumber = explode('/', $containerPort)[0]; // e.g., "80/tcp" => "80"
                $this->ports[] = new DockerSingleContainerPort($hostPort, $containerPortNumber);
            }
        }

    }

    /**
     * @throws JsonException
     */
    final public static function fromStdClass(mixed $portBindings): self
    {
        if ($portBindings instanceof stdClass) {
            $portBindings = json_decode(json_encode($portBindings, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return new self($portBindings);
    }

    /**
     * Add a new port mapping.
     */
    final public function addPort(string $hostPort, string $containerPort): void
    {
        $this->ports[] = new DockerSingleContainerPort($hostPort, $containerPort);
    }

    /**
     * Get all port mappings.
     *
     * @return DockerSingleContainerPort[]
     */
    final public function getAllPorts(): array
    {
        return $this->ports;
    }

    final public function getPorts(): array
    {
        return $this->getAllPorts();
    }

    /**
     * String representation of all port mappings.
     */
    final public function __toString(): string
    {
        return implode(', ', array_map(static fn($port) => (string)$port, $this->ports));
    }

    final public function removePortByIndex(int $index): void
    {
        unset($this->ports[$index]);
    }

    // In your Ports class

    /**
     * @throws Exception
     */
    final public function removePort(string $hostPort, string $containerPort): void
    {
        foreach ($this->ports as $key => $port) {
            if ($port->getHostPort() === $hostPort && $port->getContainerPort() === $containerPort) {
                unset($this->ports[$key]);
                // Reindex the array to maintain consistency
                $this->ports = array_values($this->ports);
                return;
            }
        }
        throw new RuntimeException("Port $hostPort:$containerPort not found.");
    }

}
