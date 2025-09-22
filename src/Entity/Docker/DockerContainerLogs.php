<?php /** @noinspection PhpUnused */

namespace App\Entity\Docker;

use App\Helper\DockerHelper;

class DockerContainerLogs
{
    private array $logs;
    final public function __construct(string $containerId)
    {
        $helper = DockerHelper::getInstance();
        $logs = $helper->getContainerLogs($containerId);
        $this->logs = $this->parse($logs);
    }

    final public function getLogs(): array
    {
        return $this->logs;
    }

    private function parse(string $log): array
    {
        $lines = explode("\n", trim($log));
        $parsedLogs = [];
        foreach ($lines as $line) {
           $parsedLogs[] = new DockerSingleContainerLog($line);
        }
        return $parsedLogs;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function extractTimestamp(string $logLine): ?string
    {
        preg_match('/\[(.*?)]/', $logLine, $matches);
        return $matches[1] ?? null;
    }
}