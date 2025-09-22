<?php

namespace App\Middleware;

class LogMiddleware extends Middleware
{
    final public function run(): void
    {
        $this->logRequest();
    }

    private function logRequest(): void
    {
        $log = date('Y-m-d H:i:s') . ' - ' . $this->httpRequest->getRequestMethod() . ' - ' . $this->httpRequest->getRequestUri() . ' - ' . $this->httpRequest->getRemoteAddr() . PHP_EOL;
        file_put_contents(__DIR__ . '/../../logs/request.log', $log, FILE_APPEND);
    }
}