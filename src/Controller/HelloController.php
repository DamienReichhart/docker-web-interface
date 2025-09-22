<?php

namespace App\Controller;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HelloController extends FrontController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function index() : string
    {
        return $this->render('hello.twig');
    }

    final public function test(int $id) : string
    {
        return 'Hello ' . $id;
    }
}