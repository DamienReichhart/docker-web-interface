<?php /** @noinspection GlobalVariableUsageInspection */

namespace App;

class Session
{
    private array $session;
    final public function __construct()
    {
        session_start();
        $this->session = $_SESSION;
    }

    final public function get(string $key) : mixed
    {
        return $this->session[$key] ?? null;
    }

    final public function set(string $key, string $value): void
    {
        $this->session[$key] = $value;
        $_SESSION[$key] = $value;
    }

    final public function remove(string $string): void
    {
        unset($this->session[$string], $_SESSION[$string]);
    }
}