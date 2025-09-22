<?php /** @noinspection PhpUnused */

namespace App\Entity\Docker;

use App\Helper\DockerHelper;

class DockerContainerStats
{
    private string $cpuUsage;
    private string $memoryUsage;

    final public function __construct(string $containerId)
    {
        $helper = DockerHelper::getInstance();
        $output = $helper->getContainerStats($containerId);
        $parsed = $this->parse($output);
        $this->cpuUsage = $parsed[0]['cpu_usage'];
        $this->memoryUsage = $parsed[0]['mem_usage'];
    }


    final public function getCpuUsage(): string
    {
        return $this->cpuUsage;
    }

    final public function getMemoryUsage(): string
    {
        return $this->memoryUsage;
    }

    private function parse(string $dockerStatsOutput) : array
    {
        $lines = explode("\n", trim($dockerStatsOutput)); // Split the output by new lines
        $data = [];

        foreach ($lines as $line) {
            // Skip the header line
            if (str_contains($line, 'CONTAINER ID')) {
                continue;
            }

            // Split each line into fields by spaces
            $columns = preg_split('/\s+/', $line);

            if (count($columns) >= 7) {

                $data[] = [
                    'container_id' => $columns[0],
                    'name' => $columns[1],
                    'cpu_usage' => $columns[2],
                    'mem_usage' => $columns[3] . ' ' . $columns[4],
                    'mem_limit' => $columns[6]
                ];
            }
        }

        return $data;
    }

    final public function __toString(): string
    {
        return "CPU Usage: $this->cpuUsage, Memory Usage: $this->memoryUsage";
    }

}