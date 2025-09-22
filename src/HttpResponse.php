<?php /** @noinspection PhpUnused */

namespace App;

class HttpResponse {
    private int $statusCode;
    private array $headers;
    private string $body;

    final public function __construct(int $statusCode = 200,string $body = '',array $headers = []) {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    // Set the status code for the response
    final public function setStatusCode(int $statusCode) : void
    {
        $this->statusCode = $statusCode;
    }

    // Get the status code for the response
    final public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    // Set the headers for the response
    final public function setHeaders(array $headers) : void
    {
        $this->headers = $headers;
    }

    // Get the headers for the response
    final public function getHeaders() : array
    {
        return $this->headers;
    }

    // Set the body of the response
    final public function setBody(string $body) : void
    {
        $this->body = $body;
    }

    // Get the body of the response
    final public function getBody() : string
    {
        return $this->body;
    }

    final public function append(?string $body) : void
    {
        if ($body !== null) {
            $this->body .= $body;
        }
    }

    // Send the response to the client
    final public function send() : void
    {
        // Set the status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $header) {
            header($header);
        }

        // Send the body
        echo $this->body;
    }

    final public function isEmpty() : bool
    {
        return !(isset($this->routes) && $this->routes !== '');
    }

    final public function addHeader(string $header) : void
    {
        $this->headers[] = $header;
    }
}