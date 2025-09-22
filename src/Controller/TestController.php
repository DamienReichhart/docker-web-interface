<?php /** @noinspection PhpUnused */

namespace App\Controller;

use App\Helper\DockerHelper;
use App\Helper\SshHelper;

class TestController extends FrontController
{
    final public function sshTest(): void
    {

        $ssh = new SshHelper('192.168.20.40', 'dockeruser', 'dockerpassword', 22, true); // Updated with real connection info
        

        $docker = DockerHelper::getInstance($ssh);

        $containers = $docker->getContainers()->getContainers();

        var_dump($containers[0]->getId());

        //$docker->stopContainer($containers[0]->getId());

        //$docker->runImage('mariadb:latest', ['MARIADB_ROOT_PASSWORD=rootPassword']);


        
    }
}
