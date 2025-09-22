<?php

namespace App\Middleware;

use Exception;

class SecurityMiddleware extends Middleware
{
    /**
     * @throws Exception
     */
    final public function run(): void
    {
        $this->checkSecurity();
    }


    /**
     * @throws Exception
     */
    private function checkSecurity(): void
    {
        if ($this->route->getSecurityLevel() >= 3) {
            $adminMiddleware = new AdminMiddleware($this->httpRequest, $this->route, $this->httpResponse, $this->session);
            $adminMiddleware->run();
        }
    }
}