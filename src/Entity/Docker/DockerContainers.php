<?php

namespace App\Entity\Docker;

class DockerContainers
{
    private array $containers;

    final public function __construct(string $output)
    {
        $containersData = $this->parse($output);
        $this->containers = [];
        foreach ($containersData as $containerData) {
            $container = new DockerSingleContainer($containerData['id']);
            
            // Store all the raw data in the container object
            if (isset($containerData['status'])) {
                $container->setRawStatus($containerData['status']);
            }
            
            if (isset($containerData['name'])) {
                $container->setRawName($containerData['name']);
            }
            
            if (isset($containerData['image'])) {
                $container->setRawImage($containerData['image']);
            }
            
            if (isset($containerData['ports'])) {
                $container->setRawPorts($containerData['ports']);
            }
            
            $this->containers[] = $container;
        }
    }

    final public function getContainers(): array
    {
        return $this->containers;
    }

    private function parse(string $output): array
    {
        // Split the output by newlines to process each line
        $lines = explode("\n", trim($output));

        // Initialize an array to store container data
        $containersData = [];

        // Loop through each line of the output
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // We expect tab-separated columns from the docker ps format string
            $parts = explode("\t", $line);
            
            // Need at least id, name, image, status for basic functionality
            if (count($parts) >= 4) {
                $status = trim($parts[3]);
                
                $containersData[] = [
                    'id' => trim($parts[0]),
                    'name' => trim($parts[1]),
                    'image' => trim($parts[2]),
                    'status' => $status,
                    'ports' => isset($parts[4]) ? trim($parts[4]) : '',
                    'command' => isset($parts[5]) ? trim($parts[5]) : ''
                ];
            }
        }

        // Return the array of container data
        return $containersData;
    }
}