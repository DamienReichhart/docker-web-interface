<?php

namespace App\Entity\Docker;


use Exception;
use RuntimeException;

class DockerContainerEnv
{
    private array $envs;
    final public function __construct(array $envs)
    {
        foreach ($envs as $env) {
            $explodedEnv = explode('=', $env);
            $this->envs[] = new DockerSingleContainerEnv($explodedEnv[0], $explodedEnv[1]);
        }
    }

    final public function getEnvs(): array
    {
        return $this->envs;
    }

    final public function addEnv(mixed $envName, mixed $envValue): void
    {
        $this->envs[] = new DockerSingleContainerEnv($envName, $envValue);
    }

    final public function getAllEnvs(): array
    {
        return $this->getEnvs();
    }

    /**
     * @throws Exception
     */
    final public function removeEnv(string $envName, string $envValue): void
    {
        foreach ($this->envs as $key => $env) {
            if ($env->getKey() === $envName && $env->getValue() === $envValue) {
                unset($this->envs[$key]);
                $this->envs = array_values($this->envs);
                return;
            }
        }
        throw new RuntimeException("Env $envName:$envValue not found.");
    }
}