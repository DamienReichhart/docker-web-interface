<?php

namespace App;

use App\Controller\ErrorController;
use JsonException;


class Router
{
    private array $routes;
    private HttpRequest $httpRequest;
    private HttpResponse $httpResponse;
    private Session $session;
    public function __construct(HttpRequest $httpRequest, httpResponse $httpResponse, Session $session)
    {
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
        $this->session = $session;
        $jsonRoutes = file_get_contents(__DIR__ . '/../routes.json');
        try {
            $this->routes = json_decode($jsonRoutes, true, 5, JSON_THROW_ON_ERROR);
        } catch (JsonException) {                       // If there is an error loading the routes
            $errorController = new ErrorController($this->httpRequest, $this->httpResponse, $this->session);
            $errorController->error500();
        }
    }

    private function searchRoute(string $URI, string $method) : Route|null
    {
        foreach ($this->routes[$method] as $path => $route) {
            if (preg_match($path, $URI)) {
                return new Route([$path => $route], $URI, $method);
            }
        }
        return null;
    }

    private function callMiddlewares(array $middlewares, Route $route) : void {
        foreach ($middlewares as $middleware) {
            $middlewareClassName = 'App\\Middleware\\' . $middleware;
            $myMiddleware = new $middlewareClassName($this->httpRequest, $route, $this->httpResponse, $this->session);
            $myMiddleware->run();
        }
    }


    final public function route() : void
    {
        $method = $this->httpRequest->getRequestMethod();
        $URI = $this->httpRequest->getRequestUri();
        $route = $this->searchRoute($URI, $method);

        if ($route === null) {
            $controller = new ErrorController($this->httpRequest, $this->httpResponse, $this->session);
            $controller->error404();
        }


        $controller = $route->getController();
        $action = $route->getAction();
        $params = $route->getParams();
        $controllerClassName = 'App\\Controller\\' . $controller;


        $this->callMiddlewares($route->getMiddlewares()->getEntryMiddleware(), $route);

        $myController = new $controllerClassName($this->httpRequest, $this->httpResponse, $this->session);
        $this->httpResponse->append($myController->$action(...$params));

        $this->callMiddlewares($route->getMiddlewares()->getExitMiddleware(), $route);

        $this->httpResponse->send();
    }


}

