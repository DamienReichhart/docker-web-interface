<?php

namespace App\Entity\Docker;

class Dockerfiles
{
    private array $dockerfiles;

    public function __construct()
    {
        $this->dockerfiles = [];
        $dockerfilesString = array_filter(scandir('../atelierHub'), function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'dockerfile';
        });
        foreach ($dockerfilesString as $dockerfile) {
            $this->dockerfiles[] = new SingleDockerfile($dockerfile);
        }
    }

    public function getDockerfiles(): array
    {
        return $this->dockerfiles;
    }
}