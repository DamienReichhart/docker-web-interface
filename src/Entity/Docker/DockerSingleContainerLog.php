<?php

namespace App\Entity\Docker;

class DockerSingleContainerLog
{
    private string $message;
    final public function __construct(string $message)
    {
        $this->message = $message;
    }

    final public function getMessage(): string
    {
        return $this->message;
    }

    final public function __toString(): string
    {
        return $this->message;
    }

}