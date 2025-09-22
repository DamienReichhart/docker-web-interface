<?php /** @noinspection PhpUnused */
/** @noinspection PhpUnusedPrivateMethodInspection */
/** @noinspection MethodVisibilityInspection */

/** @noinspection NotOptimalRegularExpressionsInspection */

namespace App\Controller;

use App\Helper\DockerHelper;
use App\Helper\SshHelper;
use App\HttpRequest;
use App\HttpResponse;
use App\Model\ManageServer;
use App\Model\Server;
use App\Model\User;
use App\Session;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use App\Filters;
use Twig\TwigFilter;

class Controller
{
    protected Environment $twig;
    protected TemplateWrapper $template;
    protected ?User $userLogged = null;
    protected HttpRequest $httpRequest;
    protected HttpResponse $httpResponse;
    protected Session $session;

    public function __construct(HttpRequest $httpRequest, HttpResponse $httpResponse, Session $session)
    {
        $this->session = $session;
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
        try {
            $this->userLogged = new User(["id" => $this->session->get('user_id'),]);
        } catch (Exception) {
            // nothing to do
        }

        $this->initTwig();
    }

    final public function redirect(string $url): void
    {
        $url = 'Location: ' . $url;
        header($url);
        exit();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    private function setTemplate(string $templatePath): void
    {
        $this->template = $this->twig->load($templatePath);

    }

    private function initTwig(): void
    {
        $loader = new FilesystemLoader('/var/www/html/template');
        $this->twig = new Environment($loader, [
            'debug' => true,
            'cache' => '/var/www/html/cache/twig',
        ]);

        $collumnNameFilter = new TwigFilter('collumnName', function (string $str) {
            return Filters::collumnToName($str);
        });

        $removeFirstCharacterFilter = new TwigFilter('removeFirstCharacter', function (string $str) {
            return Filters::removeFirstCharacter($str);
        });

        $this->twig->addFilter($collumnNameFilter);
        $this->twig->addFilter($removeFirstCharacterFilter);
        $this->twig->addExtension(new IntlExtension());
        $this->twig->addExtension(new DebugExtension());
    }

    /** @noinspection PhpSameParameterValueInspection */
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @noinspection ProperNullCoalescingOperatorUsageInspection
     */
    private function display(string $templatePath, array $data = []): void
    {
        $this->setTemplate($templatePath);

        $data['userLogged'] = $this->userLogged ?? false;

        $this->template->display($data);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final protected function render(string $templatePath, array $data = []): string
    {
        $this->setTemplate($templatePath);

        return $this->template->render($data);
    }

    final protected function getDockerHelper(Server $server): DockerHelper
    {
        $access = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        $sshHelper = new SshHelper($server->getHost(), $access->getSshUserManageServers(), $access->getSshUserPasswordManageServers());
        return DockerHelper::getInstance($sshHelper);
    }

    final protected function getSshHelper(Server $server): SshHelper
    {
        $access = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        return new SshHelper($server->getHost(), $access->getSshUserManageServers(), $access->getSshUserPasswordManageServers());
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final protected function webError(?int $error) : void
    {
        if ($error === null) {
            $error = 500;
        }
        $this->display('error/' . $error . '.twig');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final protected function renderWebError(?int $error) : string
    {
        if ($error === null) {
            $error = 500;
        }
        return $this->render('error/' . $error . '.twig');
    }

    private function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    private function getHttpResponse(): HttpResponse
    {
        return $this->httpResponse;
    }

    /**
     * Encodes a string for use in a URL.
     *
     * @param string $value The string to encode.
     * @return string The URL-encoded string.
     */
    final protected function encodeUrl(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * Decodes a URL-encoded string.
     *
     * @param string $encodedValue The URL-encoded string to decode.
     * @return string The decoded string.
     */
    final protected function decodeUrl(string $encodedValue): string
    {
        // Use rawurldecode to decode the string
        return rawurldecode($encodedValue);
    }
}