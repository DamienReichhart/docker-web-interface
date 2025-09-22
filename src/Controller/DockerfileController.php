<?php /** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\Docker\Dockerfiles;
use App\Entity\Form\BasicForm;
use App\Model\Server;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DockerfileController extends FrontController
{

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function index() : string
    {
        $dockerfiles = new Dockerfiles();
        return $this->render('dockerfile/index.twig', [
            'dockerfiles' => $dockerfiles,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    final public function show(string $dockerfileName) : string
    {
        // Check if the filename already includes .dockerfile extension
        $filename = str_ends_with($dockerfileName, '.dockerfile') 
            ? $dockerfileName 
            : $dockerfileName . '.dockerfile';
        
        $fileContent = file_get_contents('../atelierHub/' . $filename);
        
        // Remove .dockerfile extension for display
        $displayName = str_ends_with($dockerfileName, '.dockerfile') 
            ? substr($dockerfileName, 0, -11) 
            : $dockerfileName;
        
        return $this->render('dockerfile/show.twig', [
            'fileContent' => $fileContent,
            'dockerfileName' => $displayName
        ]);
    }

    final public function save(string $dockerfileName) : string
    {
        $fileContent = (json_decode(file_get_contents('php://input'), true))['fileContent'];
        file_put_contents('../atelierHub/' . $dockerfileName . '.dockerfile', $fileContent);
        $this->httpResponse->setStatusCode(200);
        return json_encode(['status' => 'ok']);
    }

    final public function delete(string $dockerfileName) : string
    {
        // Check if the filename already includes .dockerfile extension
        $filename = str_ends_with($dockerfileName, '.dockerfile') 
            ? $dockerfileName 
            : $dockerfileName . '.dockerfile';
        
        unlink('../atelierHub/' . $filename);
        return $this->redirect('/dockerfiles') ?? '';
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function add() : string
    {
        return $this->render('dockerfile/add.twig');
    }

    final public function addPost() : string
    {
        $dockerfileName = $this->httpRequest->getPostElement('dockerfileName');
        $template = $this->httpRequest->getPostElement('template') ?? 'blank';
        
        $templateContent = '';
        
        if ($template === 'nginx') {
            $templateContent = <<<EOT
FROM nginx:alpine

# Copy custom nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy static website content
COPY ./html /usr/share/nginx/html

# Expose port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
EOT;
        } elseif ($template === 'php') {
            $templateContent = <<<EOT
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \\
    libpng-dev \\
    libjpeg-dev \\
    libfreetype6-dev \\
    zip \\
    unzip \\
    && docker-php-ext-configure gd --with-freetype --with-jpeg \\
    && docker-php-ext-install gd pdo pdo_mysql

# Enable apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
EOT;
        } elseif ($template === 'node') {
            $templateContent = <<<EOT
FROM node:16-alpine

# Create app directory
WORKDIR /usr/src/app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy app source
COPY . .

# Expose the port
EXPOSE 3000

# Start the app
CMD ["node", "index.js"]
EOT;
        }
        
        file_put_contents('../atelierHub/' . $dockerfileName . '.dockerfile', $templateContent);
        return $this->redirect('/dockerfile/edit/' . $dockerfileName) ?? '';
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    final public function build(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $this->getDockerHelper($server);

        // get list of dockerfiles in the directory /atelierHub and display them in a dropdown
        $dockerfiles = array_filter(scandir('../atelierHub'), function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'dockerfile';
        });

        return $this->render('docker/custom-build.twig', [
            'server' => $server,
            'dockerfiles' => $dockerfiles,
        ]);
    }

    /**
     * @throws Exception
     * @return string
     */
    final public function buildPost(string $serverRef): string
    {
        $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
        $dockerHelper = $this->getDockerHelper($server);
        $sshHelper = $this->getSshHelper($server);

        $image = $this->httpRequest->getPostElement('image');
        $sshHelper->sendFile('../atelierHub/' . $image, '/tmp/' . $image);

        $dockerHelper->buildCustomImage('/tmp/' . $image, explode('.',$image)[0]);

        return $this->redirect('/server/manage/' . $serverRef) ?? '';
    }

    /**
     * Build custom image with AJAX for real-time feedback
     * 
     * @param string $serverRef Server reference ID
     * @return string JSON response
     * @throws Exception
     */
    final public function buildAjax(string $serverRef): string
    {
        try {
            $server = Server::getWhere(['urlIdentifierServer' => $serverRef])[0];
            $dockerHelper = $this->getDockerHelper($server);
            $sshHelper = $this->getSshHelper($server);
            
            $image = $this->httpRequest->getPostElement('image');

            if (empty($image)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Dockerfile is required'
                ]);
            }
            
            // Send the Dockerfile to the server
            $localPath = '../atelierHub/' . $image;
            $remotePath = '/tmp/' . $image;
            
            if (!file_exists($localPath)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Dockerfile not found: ' . $image
                ]);
            }
            
            // Ensure file is readable before sending
            if (!is_readable($localPath)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Dockerfile exists but is not readable: ' . $image
                ]);
            }
            
            // Check file contents before sending
            $fileContents = file_get_contents($localPath);
            if ($fileContents === false || empty($fileContents)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Dockerfile is empty or could not be read: ' . $image
                ]);
            }
            
            // Transfer the file
            $sshHelper->sendFile($localPath, $remotePath);
            
            // Get the image name from the Dockerfile (without extension)
            $imageName = explode('.', $image)[0];
            
            // Build the image
            $output = $dockerHelper->buildCustomImage($remotePath, $imageName);
            
            // Check if the build was successful
            if (is_string($output) && (strpos($output, 'Successfully built') !== false || 
                strpos($output, 'Successfully tagged') !== false)) {
                
                $this->httpResponse->setStatusCode(200);
                return json_encode([
                    'success' => true,
                    'message' => 'Image built successfully',
                    'command_output' => $output,
                    'redirect' => '/server/manage/' . $serverRef
                ]);
            } else {
                // Parse the error message from the output
                $errorMessage = "Failed to build image";
                
                // Look for common Docker build errors in the output
                if (is_string($output) && preg_match('/error:.+/i', $output, $matches)) {
                    $errorMessage .= ": " . $matches[0];
                } elseif (is_string($output) && strpos($output, 'Error building image:') !== false) {
                    preg_match('/Error building image:.*/', $output, $matches);
                    $errorMessage = isset($matches[0]) ? $matches[0] : $errorMessage;
                }
                

                return json_encode([
                    'success' => false,
                    'message' => $errorMessage,
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