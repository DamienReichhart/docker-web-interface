<?php

namespace App;

use App\Controller\ErrorController;
use JsonException;
use App\Structure\RouteMiddlewaresStructure;

class Route
{
    private string $routePath;
    private string $controller;
    private string $action;
    private array $params;
    private string $method;
    private string $URIKey;
    private RouteMiddlewaresStructure $middlewares;
    private int $securityLevel;
    public function __construct(array $routeArray, string $URI, string $method)
    {
        $this->routePath = $URI;
        $this->URIKey = key($routeArray);  // Regex pattern
        $this->controller = $routeArray[$this->URIKey]['controller'] ?? 'ErrorController';
        $this->action = $routeArray[$this->URIKey]['action'];
        $this->securityLevel = (int)($routeArray[$this->URIKey]['securityLevel'] ?? 0);
        $this->params = $this->findParams($routeArray);
        $this->method = $method;
        $this->middlewares = $this->loadMiddlewares();
    }

    private function findParams(array $routeArray): array
    {
        $params = [];
        $pathPattern = key($routeArray);

        if (preg_match($pathPattern, $this->routePath, $matches)) {
            $params = array_slice($matches, 1);
        }

        foreach ($params as $key => $param) {
            if (is_numeric($param)) {
                $params[$key] = (int) $param;
            }
        }
        return $params;
    }

    private function loadMiddlewares(): RouteMiddlewaresStructure
    {

        try {       // try load the middlewares.json file with catching errors if it fails
            $middlewaresJson = file_get_contents(__DIR__ . '/../middlewares.json');
            $middlewaresDecoded = json_decode($middlewaresJson, true,5, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $errorController = new ErrorController();
            $errorController->error500();
        }

        $entryMiddlewaresArray = $this->getSubMiddlewares($middlewaresDecoded, 'entryMiddlewares');
        $exitMiddlewaresArray = $this->getSubMiddlewares($middlewaresDecoded, 'exitMiddlewares');

        return new RouteMiddlewaresStructure(array_merge(...$entryMiddlewaresArray), array_merge(...$exitMiddlewaresArray));
    }

    private function getSubMiddlewares(array $decodedMiddlewares, string $middlewareType): array
    {
        $MiddlewaresArray = [];
        $MiddlewaresArray[] = $decodedMiddlewares['GLOBAL'][$middlewareType];
        $MiddlewaresArray[] = $decodedMiddlewares[$this->method][$this->URIKey][$middlewareType] ?? [];

        return $MiddlewaresArray;
    }

    final public function getRoutePath(): string
    {
        return $this->routePath;
    }
    final public function getController(): string
    {
        return $this->controller;
    }

    final public function getAction(): string
    {
        return $this->action;
    }

    final public function getParams(): array
    {
        return $this->params;
    }

    final public function getMethod(): string
    {
        return $this->method;
    }

    final public function getMiddlewares(): RouteMiddlewaresStructure
    {
        return $this->middlewares;
    }

    final public function getURIKey(): string
    {
        return $this->URIKey;
    }

    final public function getSecurityLevel(): int
    {
        return $this->securityLevel;
    }
}