<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Controller;

use App\Model\User;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AuthController extends FrontController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function index() : string
    {
        return $this->render('auth/login.twig');
    }

    /** @noinspection PhpUnused */
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws Exception
     */
    final public function loginPost(): string
    {
        try {
            $email = $this->httpRequest->getPostElement('email');
            $password = $this->httpRequest->getPostElement('password');
        } catch (RuntimeException) {
            return $this->renderWebError(500);
        }
        $user = User::getByProperty('emailUsers', $email);
        if ((count($user) > 0 ) && password_verify($password, $user[0]->getPassword())) {
            $this->session->set('user_id',$user[0]->getId());
            return $this->redirect('/server');
        }

        return $this->redirect('/login');
    }

    /** @noinspection PhpUnused */
    final public function logout(): string
    {
        $this->session->remove('user_id');
        return $this->redirect('/login');
    }

}