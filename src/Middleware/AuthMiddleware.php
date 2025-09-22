<?php

namespace App\Middleware;

class AuthMiddleware extends Middleware
{
    /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
    final public function run(): void
    {
        if (!$this->httpRequest->getUserSession() !== "") {
            $this->redirect('/login');
        }
    }
}