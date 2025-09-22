<?php

namespace App\Middleware;

use App\HttpRequest;
use App\HttpResponse;
use App\Route;
use App\Session;
use JetBrains\PhpStorm\NoReturn;

abstract class Middleware
{
    protected HttpRequest $httpRequest;
    protected Route $route;
    protected HttpResponse $httpResponse;
    protected Session $session;

    public function __construct(HttpRequest $httpRequest, Route $route, HttpResponse $httpResponse, Session $session)
    {
        $this->httpRequest = $httpRequest;
        $this->route = $route;
        $this->httpResponse = $httpResponse;
        $this->session = $session;
    }
    abstract public function run(): void;

    final public function redirect(string $location): void
    {
        header("Location: $location");
        exit;
    }
}

