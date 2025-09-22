<?php /** @noinspection PhpUnused */

namespace App\Entity\Docker;

class DockerSingleContainerEnv
{
    private string $key;
    private string $value;
    final public function __construct(string $envKey, string $envValue)
    {
        $this->key = $envKey;
        $this->value = $envValue;
    }

    final public function getKey(): string
    {
        return $this->key;
    }

    final public function getValue(): string
    {
        return $this->value;
    }

    final public function toEnvString(): string
    {
        return $this->key . '=' . $this->value;
    }

    final public function __toString(): string
    {
        return $this->toEnvString();
    }

    final public function setKey(string $key): void
    {
        $this->key = $key;
    }

    final public function setValue(string $value): void
    {
        $this->value = $value;
    }

}