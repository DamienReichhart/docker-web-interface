<?php /** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\Form\BasicForm;
use App\Model\Server;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ImageController extends FrontController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function imagesPull(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        return $this->render('docker/image-pull.twig', [
            'server' => $server,
        ]);
    }

    /**
     * @return string|void
     */
    final public function imagesPullPost(string $serverRef)
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);

        $image = $this->httpRequest->getPostElement('image');
        $dockerHelper->pullImage($image);

        return $this->redirect('/server/manage/' . $serverRef);
    }

    /**
     * Pull images using AJAX for real-time feedback
     * 
     * @param string $serverRef Server reference ID
     * @return string JSON response
     */
    final public function imagesPullAjax(string $serverRef): string
    {
        try {
            $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
            $dockerHelper = $this->getDockerHelper($server);
            
            $image = $this->httpRequest->getPostElement('image');
            
            if (empty($image)) {
                $this->httpResponse->setStatusCode(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Image name is required'
                ]);
            }
            
            // Use executeCommand instead of executeCommandInBackground to get the output
            $sshHelper = $this->getSshHelper($server);
            $command = 'docker pull ' . escapeshellcmd($image);
            $output = $sshHelper->executeCommand($command);
            
            // Check if the command was successful
            if (strpos($output, 'Downloaded newer image') !== false || 
                strpos($output, 'Image is up to date') !== false || 
                strpos($output, 'Download complete') !== false) {
                
                $this->httpResponse->setStatusCode(200);
                return json_encode([
                    'success' => true,
                    'message' => 'Image pulled successfully',
                    'command_output' => $output,
                    'redirect' => '/server/manage/' . $serverRef
                ]);
            } else {
                $this->httpResponse->setStatusCode(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to pull image',
                    'command_output' => $output
                ]);
            }
        } catch (\Exception $e) {
            $this->httpResponse->setStatusCode(500);
            return json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}