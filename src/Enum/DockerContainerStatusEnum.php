<?php

namespace App\Enum;

enum DockerContainerStatusEnum : string
{
    case RUNNING = 'RUNNING';
    case CREATED = 'CREATED';
    case ERROR = 'ERROR';
    case STARTING = 'STARTING';
    case STOPPED = 'STOPPED';
    case EXITED = 'EXITED';
}
