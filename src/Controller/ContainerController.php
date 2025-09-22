<?php /** @noinspection PhpUnused */

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Controller;

use App\Entity\Docker\DockerImages;
use App\Entity\Docker\DockerSingleContainer;
use App\Entity\Docker\DockerSingleImage;
use App\Entity\Form\BasicForm;
use App\Helper\DockerHelper;
use App\Helper\SshHelper;
use App\Model\ManageServer;
use App\Model\Server;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use RuntimeException;

class ContainerController extends FrontController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function add(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $serverAccess = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        $sshHelper = new SshHelper($server->getHost(), $serverAccess->getSshUserManageServers(), $serverAccess->getSshUserPasswordManageServers());
        DockerHelper::getInstance($sshHelper);

        $images = (new DockerImages())->getImages();
        $imgStrList = [];
        foreach ($images as $image) {
            $repoWithTag = $image->getRepoWithTag();
            $imgStrList[$repoWithTag] = $repoWithTag;
        }

        $form = new BasicForm('Ajouter un conteneur', '/container/add/' . $serverRef . '/post');
        $form->addLine('Nom du conteneur', 'containerName');
        $form->addLine('Image', 'image', 'select', $imgStrList);
        $form->addLine('Image', 'comment', 'htmlComment', ['href' => '/images/pull/' . $serverRef, 'comment' => 'Ajouter une image']);


        return $this->render('form.twig', [
            'form' => $form
        ]);
    }

    final public function startContainer(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $serverAccess = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        $sshHelper = new SshHelper($server->getHost(), $serverAccess->getSshUserManageServers(), $serverAccess->getSshUserPasswordManageServers());
        $dockerHelper = DockerHelper::getInstance($sshHelper);
        $dockerHelper->startContainer($containerId);
        return $this->redirect('/server/manage/' . $serverRef);
    }

    final public function addPost(string $serverRef): string
    {
        $containerName = $this->httpRequest->getPostElement('containerName');
        $image = $this->httpRequest->getPostElement('image');

        // Validate image format
        if (empty($image)) {
            throw new RuntimeException('Image name cannot be empty');
        }

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $access = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        $sshHelper = new SshHelper($server->getHost(), $access->getSshUserManageServers(), $access->getSshUserPasswordManageServers());
        $dockerHelper = DockerHelper::getInstance($sshHelper);

        try {
            // First pull the image
            DockerSingleImage::pull($image);
            
            // Create the container directly with the image name
            $dockerHelper->runNewContainer(
                new DockerSingleImage($image),
                $containerName
            );
        } catch (RuntimeException $e) {
            throw new RuntimeException('Failed to create container: ' . $e->getMessage());
        }

        return $this->redirect('/server/manage/' . $serverRef);
    }

    final public function delete(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $access = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        $sshHelper = new SshHelper($server->getHost(), $access->getSshUserManageServers(), $access->getSshUserPasswordManageServers());
        $dockerHelper = DockerHelper::getInstance($sshHelper);

        $dockerHelper->deleteContainer($containerId);

        return $this->redirect('/server/manage/' . $serverRef);
    }
    final public function stop(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $access = ManageServer::getWhere(['idServers' => $server->getId(), 'idUsers' => $this->session->get('user_id')])[0];
        $sshHelper = new SshHelper($server->getHost(), $access->getSshUserManageServers(), $access->getSshUserPasswordManageServers());
        $dockerHelper = DockerHelper::getInstance($sshHelper);

        $dockerHelper->stopContainer($containerId);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function show(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);


        return $this->render('container/show.twig', [
            'server' => $server,
            'container' => $container
        ]);
    }


    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function portsAdd(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        // Check if this is an AJAX request for the modal
        if ($this->httpRequest->isAjax()) {
            return $this->render('container/port_add_modal.twig', [
                'server' => $server,
                'container' => $container
            ]);
        }

        // Fallback to the full page form if needed
        $form = new BasicForm('Ajouter un port', '/container/add/' . $serverRef . '/' . $containerId . '/ports/post');
        $form->addLine('Port externe', 'externalPort');
        $form->addLine('Port interne', 'internalPort');

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function portsAddPost(string $serverRef, string $containerId): string
    {

        $externalPort = $this->httpRequest->getPostElement('externalPort');
        $internalPort = $this->httpRequest->getPostElement('internalPort');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->addPort($externalPort, $internalPort);
        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function commandEdit(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        $form = new BasicForm('Editer la commande', '/container/edit/' . $serverRef . '/' . $containerId . '/command/post');
        $form->addLine('Commande', 'command', 'text', $container->getCommand());

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function commandEditPost(string $serverRef, string $containerId): string
    {
        $command = $this->httpRequest->getPostElement('command');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->setCommand($command);
        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }


    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function portsEdit(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        // Create options array for the dropdown
        $portOptions = [];
        foreach ($container->getPortsObject()->getAllPorts() as $port) {
            $portString = $port->getHostPort() . ':' . $port->getContainerPort();
            $portOptions[$portString] = $portString;
        }

        $form = new BasicForm('Editer un port', '/container/edit/' . $serverRef . '/' . $containerId . '/ports/post');
        $form->addLine('Port à modifier', 'portId', 'select', $portOptions);
        $form->addLine('Port externe', 'externalPort');
        $form->addLine('Port interne', 'internalPort');

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function portsEditPost(string $serverRef, string $containerId): string
    {
        $portId = $this->httpRequest->getPostElement('portId');
        [$baseExternalPort, $baseInternalPort] = explode(":", $portId);
        $externalPort = $this->httpRequest->getPostElement('externalPort');
        $internalPort = $this->httpRequest->getPostElement('internalPort');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        foreach ($container->getPortsObject()->getAllPorts() as $port) {
            if ($port->getHostPort() === $baseExternalPort && $port->getContainerPort() === $baseInternalPort) {
                $port->setHostPort($externalPort);
                $port->setContainerPort($internalPort);
            }
        }
        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }



    /**
     * @throws Exception
     */
    final public function portsDeletePost(string $serverRef, string $containerId): string
    {
        $portId = $this->httpRequest->getPostElement('portId');
        if (empty($portId)) {
            throw new RuntimeException('No port selected for deletion');
        }

        [$externalPort, $internalPort] = explode(':', $portId);
        if (empty($externalPort) || empty($internalPort)) {
            throw new RuntimeException('Invalid port format');
        }

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->getPortsObject()->removePort($externalPort, $internalPort);

        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function volumesAdd(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        // Check if this is an AJAX request for the modal
        if ($this->httpRequest->isAjax()) {
            return $this->render('container/volume_add_modal.twig', [
                'server' => $server,
                'container' => $container
            ]);
        }

        // Fallback to the full page form if needed
        $form = new BasicForm('Ajouter un volume', '/container/add/' . $serverRef . '/' . $containerId . '/volumes/post');
        $form->addLine('Volume source', 'sourceVolume');
        $form->addLine('Volume cible', 'targetVolume');

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function volumesAddPost(string $serverRef, string $containerId): string
    {
        $sourceVolume = $this->httpRequest->getPostElement('sourceVolume');
        $targetVolume = $this->httpRequest->getPostElement('targetVolume');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->addVolume($sourceVolume, $targetVolume);

        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function volumesEdit(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        // Create options array for the dropdown
        $volumeOptions = [];
        foreach ($container->getVolumes()->getAllVolumes() as $volume) {
            $volumeString = $volume->getSourceVolume() . ':' . $volume->getTargetVolume();
            $volumeOptions[$volumeString] = $volumeString;
        }

        $form = new BasicForm('Editer un volume', '/container/edit/' . $serverRef . '/' . $containerId . '/volumes/post');
        $form->addLine('Volume à modifier', 'volumeId', 'select', $volumeOptions);
        $form->addLine('Volume source', 'sourceVolume');
        $form->addLine('Volume cible', 'targetVolume');

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function volumesEditPost(string $serverRef, string $containerId): string
    {
        $volumeId = $this->httpRequest->getPostElement('volumeId');
        [$baseSourceVolume,$baseTargetVolume] = explode(":", $volumeId);

        $sourceVolume = $this->httpRequest->getPostElement('sourceVolume');
        $targetVolume = $this->httpRequest->getPostElement('targetVolume');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        foreach ($container->getVolumes()->getAllVolumes() as $volume) {
            if ($volume->getSourceVolume() === $baseSourceVolume && $volume->getTargetVolume() === $baseTargetVolume) {
                $volume->setSourceVolume($sourceVolume);
                $volume->setTargetVolume($targetVolume);
            }
        }
        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    

    /**
     * @throws Exception
     */
    final public function volumesDeletePost(string $serverRef, string $containerId): string
    {
        $volumeId = $this->httpRequest->getPostElement('volumeId');
        [$sourceVolume, $targetVolume] = explode(":", $volumeId);

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->getVolumes()->removeVolume($sourceVolume, $targetVolume);

        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function envsAdd(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        // Check if this is an AJAX request for the modal
        if ($this->httpRequest->isAjax()) {
            return $this->render('container/env_add_modal.twig', [
                'server' => $server,
                'container' => $container
            ]);
        }

        // Fallback to the full page form if needed
        $form = new BasicForm('Ajouter une variable d\'environnement', '/container/add/' . $serverRef . '/' . $containerId . '/envs/post');
        $form->addLine('Nom de la variable', 'envName');
        $form->addLine('Valeur de la variable', 'envValue');

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function envsAddPost(string $serverRef, string $containerId): string
    {
        $envName = $this->httpRequest->getPostElement('envName');
        $envValue = $this->httpRequest->getPostElement('envValue');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->addEnv($envName, $envValue);

        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function envsEdit(string $serverRef, string $containerId): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);

        // Create options array for the dropdown
        $envOptions = [];
        foreach ($container->getEnvs()->getAllEnvs() as $env) {
            $envString = $env->getKey() . '=' . $env->getValue();
            $envOptions[$envString] = $envString;
        }

        $form = new BasicForm('Editer une variable d\'environnement', '/container/edit/' . $serverRef . '/' . $containerId . '/envs/post');
        $form->addLine('Variable à modifier', 'envId', 'select', $envOptions);
        $form->addLine('Nom de la variable', 'envName');
        $form->addLine('Valeur de la variable', 'envValue');

        return $this->render('form.twig', [
            'form' => $form,
            'server' => $server,
            'container' => $container
        ]);
    }

    final public function envsEditPost(string $serverRef, string $containerId): string
    {
        $envId = $this->httpRequest->getPostElement('envId');
        [$baseEnvName, $baseEnvValue] = explode("=", $envId);
        $envName = $this->httpRequest->getPostElement('envName');
        $envValue = $this->httpRequest->getPostElement('envValue');

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        
        // First remove the old environment variable
        $container->getEnvs()->removeEnv($baseEnvName, $baseEnvValue);
        
        // Then add the new one with the updated name and value
        $container->addEnv($envName, $envValue);
        
        // Recreate the container with updated environment variables
        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }



    /**
     * @throws Exception
     */
    final public function envsDeletePost(string $serverRef, string $containerId): string
    {
        $envId = $this->httpRequest->getPostElement('envId');
        if (empty($envId)) {
            throw new RuntimeException('No environment variable selected for deletion');
        }

        [$envName, $envValue] = explode("=", $envId);
        if (empty($envName) || empty($envValue)) {
            throw new RuntimeException('Invalid environment variable format');
        }

        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $container = new DockerSingleContainer($containerId);
        $container->getEnvs()->removeEnv($envName, $envValue);

        $dockerHelper->reRunContainer($container);

        return $this->redirect('/server/manage/' . $serverRef);
    }
}