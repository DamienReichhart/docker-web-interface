<?php

namespace App\Entity\Docker;

class DockerSingleContainerVolume
{
    private $source;
    private $target;

    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function toVolumeMap(): string
    {
        return $this->source . ':' . $this->target;
    }

    public function __toString(): string
    {
        return $this->toVolumeMap();
    }

    final public function setSourceVolume(string $source): void
    {
        $this->source = $source;
    }

    final public function setTargetVolume(string $target): void
    {
        $this->target = $target;
    }

    final public function getSourceVolume(): string
    {
        return $this->source;
    }

    final public function getTargetVolume(): string
    {
        return $this->target;
    }
}