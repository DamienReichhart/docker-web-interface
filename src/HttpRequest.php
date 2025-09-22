<?php /** @noinspection PhpUnused */

/** @noinspection GlobalVariableUsageInspection */

namespace App;

use RuntimeException;

class HttpRequest
{
    private string $requestMethod;
    private string $requestUri;
    private string $remoteAddr;
    private string $userAgent;
    private string $userSession;
    private array $get;
    private array $post;

    public function __construct()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestUri = $_SERVER['REQUEST_URI'];
        $this->remoteAddr = $_SERVER['REMOTE_ADDR'];
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->userSession = $_SESSION['user'] ?? '';
        $this->post = $_POST;
        $this->get = $_GET;
    }
    final public function getRequestMethod() : string
    {
        return $this->requestMethod;
    }

    final public function getRequestUri() : string
    {
        return $this->requestUri;
    }

    final public function getRemoteAddr() : string
    {
        return $this->remoteAddr;
    }

    final public function getUserAgent(): mixed
    {
        return $this->userAgent;
    }

    final public function getUserSession(): string
    {
        return $this->userSession;
    }
    final public function getPostElement(string $name): string
    {
        if (!isset($_POST[$name])) {
            throw new RuntimeException('Post element ' . $name . ' not found');
        }
        return $_POST[$name];
    }

    final public function getGetElement(string $key = null): mixed
    {
        return $this->get[$key] ?? null;
    }

    final public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}