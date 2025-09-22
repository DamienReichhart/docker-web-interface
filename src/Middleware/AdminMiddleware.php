<?php

namespace App\Middleware;

use App\Model\User;
use Exception;
use RuntimeException;

class AdminMiddleware extends Middleware
{
    /**
     * @throws Exception
     */
    final public function run(): void
    {
        $this->checkAdmin();
    }

    /**
     * @throws Exception
     */
    private function checkAdmin(): void
    {
        $userId = (int)($this->session->get('user_id') ?? -1);
        try {
            $user = new User(["id" => $userId]);
            if (!$user->isAdmin()) {
                $this->redirect('/login');
            }
        } catch (RuntimeException) {            // If the user is not found
            $this->redirect('/login');
        }
    }
}