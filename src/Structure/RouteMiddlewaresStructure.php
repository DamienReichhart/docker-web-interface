<?php

namespace App\Structure;

class RouteMiddlewaresStructure
{
    private array $entryMiddleware;
    private array $exitMiddleware;

    public function __construct(array $entryMiddleware, array $exitMiddleware)
    {
        $this->entryMiddleware = $entryMiddleware;
        $this->exitMiddleware = $exitMiddleware;
    }

    final public function getEntryMiddleware(): array
    {
        return $this->entryMiddleware;
    }

    final public function getExitMiddleware(): array
    {
        return $this->exitMiddleware;
    }
}