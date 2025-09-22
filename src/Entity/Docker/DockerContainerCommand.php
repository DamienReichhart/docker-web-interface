<?php /** @noinspection PhpUnused */

namespace App\Entity\Docker;

class DockerContainerCommand
{
    private string $command;

    public function __construct(array $command)
    {
        $this->command = '';
        foreach ($command as $cmd) {
            // If the command contains spaces and isn't already quoted, wrap it in quotes
            if (str_contains($cmd, ' ') && !preg_match('/^[\'"].*[\'"]$/', $cmd)) {
                $this->command .= " " . $cmd;
            } else {
                $this->command .= " " . $cmd;
            }
        }
        $this->command = trim($this->command);
    }

    public static function fromStdClass(array $cmd): DockerContainerCommand
    {
        return new self($cmd);
    }

    final public function getCommand(): string
    {
        return $this->command;
    }

    final public function getCommandString(): string
    {
        return $this->command;
    }

    final public function __toString(): string
    {
        return $this->getCommandString();
    }
}