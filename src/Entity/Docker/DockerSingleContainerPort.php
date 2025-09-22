<?php

namespace App\Entity\Docker;

class DockerSingleContainerPort
{
    private string $hostPort;
    private string $containerPort;

    final public function __construct(string $hostPort, string $containerPort)
    {
        $this->hostPort = $hostPort;
        $this->containerPort = $containerPort;
    }

    final public function getHostPort(): string
    {
        return $this->hostPort;
    }

    final public function getContainerPort(): string
    {
        return $this->containerPort;
    }

    final public function __toString(): string
    {
        return "$this->hostPort:$this->containerPort";
    }

    final public function toPortMap(): string
    {
        return $this->__toString();
    }

    final public function setHostPort(mixed $externalPort): void
    {
        $this->hostPort = $externalPort;
    }

    final public function setContainerPort(mixed $internalPort): void
    {
        $this->containerPort = $internalPort;
    }
}
