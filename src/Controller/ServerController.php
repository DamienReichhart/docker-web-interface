<?php /** @noinspection PhpUnused */

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Controller;

use App\Entity\Form\ModelAddForm;
use App\Entity\Form\ModelEditForm;
use App\Helper\DockerHelper;
use App\Helper\SshHelper;
use App\Model\ManageServer;
use App\Model\Server;
use PDOException;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ServerController extends FrontController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function index() : string
    {
        $manageServers = ManageServer::getWhere(['idUsers' => $this->userLogged->getId()]);
        $idServers = array_map(static fn($manageServer) => $manageServer->idServers, $manageServers);
        $servers = [];

        foreach ($idServers as $idServer) {
            $servers[] = Server::getWhere(['id' => $idServer])[0];
        }

        return $this->render('server/index.twig', ['servers'=>$servers] );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function edit(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $form = new ModelEditForm(Server::class);
        $form->setModelInstance($server);
        return $this->render('form.twig', ['form' => $form->renderForm('/server/edit/' . $serverRef . '/post')]);
    }

    final public function editPost(string $serverRef): string
    {

        $nameServer = $this->httpRequest->getPostElement('nameServers');
        $host = $this->httpRequest->getPostElement('host');
        
        // Get description if provided, otherwise set to null
        try {
            $description = $this->httpRequest->getPostElement('description');
        } catch (RuntimeException) {
            $description = null;
        }

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];

        $server->setNameServers($nameServer);
        $server->setHost($host);
        $server->setDescription($description);
        $server->save();
        return $this->redirect('/server');
    }

    final public function delete(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];

        $serverAccess = ManageServer::getWhere(['idServers' => $server->getId()])[0];

        $serverAccess->delete();

        return $this->redirect('/server');
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function add(): string
    {
        $form = new ModelAddForm(Server::class);
        return $this->render('form.twig', ['form' => $form->renderForm('/server/add/post')]);
    }

    final public function addPost(): string
    {

        $nameServer = $this->httpRequest->getPostElement('nameServers');
        $host = $this->httpRequest->getPostElement('host');
        
        // Get description if provided, otherwise set to null
        try {
            $description = $this->httpRequest->getPostElement('description');
        } catch (RuntimeException) {
            $description = null;
        }

        $new_server = new Server();
        $new_server->setNameServers($nameServer);
        $new_server->setHost($host);
        $new_server->setDescription($description);
        try {
            $new_server->save();
        }catch(PDOException) {
            return $this->redirect('/server/add');
        }

        return $this->redirect('/server');
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function addAccess(): string
    {
        $form = new ModelAddForm(ManageServer::class);
        $sshPublicKey = $this->getLocalPublicKey();
        return $this->render('form.twig', ['form' => $form->renderForm('/server/access/add/post'), 'comments' => ['Remarque : Copiez le contenu de la clef public suivante dans les clef autorisées si vous souhaitez utiliser l\'authentification par clef ssh', $sshPublicKey, ]]);
    }

    private function getLocalPublicKey(): ?string
    {
        $homeDir = '/root';

        $expandedPath = preg_replace('#^~#', $homeDir, '~/.ssh/id_rsa.pub');

        if (!file_exists($expandedPath) || !is_readable($expandedPath)) {
            return null;
        }

        return file_get_contents($expandedPath);
    }


    final public function addAccessPost(): string
    {
        $idServers = $this->httpRequest->getPostElement('idServers');
        $sshUserManageServers = $this->httpRequest->getPostElement('sshUserManageServers');
        try {
            $sshUserPasswordManageServers = $this->httpRequest->getPostElement('sshUserPasswordManageServers');
        }catch (RuntimeException) {
            $sshUserPasswordManageServers = null;
        }


        $new_manage_server = new ManageServer();
        $new_manage_server->setIdUsers($this->userLogged->getId());
        $new_manage_server->setIdServers(Server::getWhere(['id' => $idServers])[0]->getId());
        $new_manage_server->setSshUserManageServers($sshUserManageServers);
        $new_manage_server->setSshUserPasswordManageServers($sshUserPasswordManageServers);

        try {
            $new_manage_server->insert();
        }catch(PDOException) {
            return $this->redirect('/server/add/access');
        }

        return $this->redirect('/server');
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function manage(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $serverAccess = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        
        try {
            $sshHelper = new SshHelper($server->getHost(), $serverAccess->getSshUserManageServers(), $serverAccess->getSshUserPasswordManageServers());
            $dockerHelper = DockerHelper::getInstance($sshHelper);
            
            try {
                // Get Docker containers
                $dockerContainers = $dockerHelper->getContainers()->getContainers();
                
                // Get Docker images
                $imagesOutput = $dockerHelper->getImages();
                $images = [];
                
                if (!empty($imagesOutput)) {
                    $imageLines = explode("\n", $imagesOutput);
                    foreach ($imageLines as $line) {
                        if (empty(trim($line))) continue;
                        
                        $parts = explode("\t", $line);
                        if (count($parts) >= 5) {
                            $images[] = [
                                'id' => $parts[0],
                                'repo' => $parts[1],
                                'tag' => $parts[2],
                                'size' => $parts[3],
                                'created' => $parts[4]
                            ];
                        }
                    }
                }
                
                // Check if Docker Compose is supported
                $composeSupported = $dockerHelper->isComposeSupported();
                
                return $this->render('server/manage.twig', [
                    'server' => $server, 
                    'containers' => $dockerContainers,
                    'images' => $images,
                    'composeSupported' => $composeSupported,
                    'serverAccess' => $serverAccess
                ]);
            } catch (RuntimeException $dockerEx) {
                // Check for Docker permission errors specifically
                if (stripos($dockerEx->getMessage(), 'permission denied') !== false) {
                    $this->session->set('error_message', 'Docker permission denied: The SSH user does not have permissions to access Docker. Please add the SSH user to the docker group on the remote server.');
                } else {
                    $this->session->set('error_message', 'Docker error: ' . $dockerEx->getMessage());
                }
                
                return $this->redirect('/server');
            }
            
        } catch (RuntimeException $e) {
            // Log the error
            error_log('Error connecting to server: ' . $e->getMessage());
            
            // Set a flash message
            $this->session->set('error_message', 'Failed to connect to server: ' . $e->getMessage());
            
            return $this->redirect('/server');
        }
    }

}
