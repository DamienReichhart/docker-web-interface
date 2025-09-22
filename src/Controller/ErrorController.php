<?php /** @noinspection PhpUnused */

namespace App\Controller;

use JetBrains\PhpStorm\NoReturn;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ErrorController Extends Controller
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function index(): void
    {
        $this->webError(404);
        exit();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function error404() : void
    {
        $this->webError(404);
        exit();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function error500() : void
    {
        $this->webError(500);
        exit();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function error204() : string
    {
        return $this->renderWebError(204);
    }

}