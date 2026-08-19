<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener;

enum WorkerExitStatus: int
{
    case SUCCESS = 0;
    case ERROR = 1;
    case MEMORY_LIMIT = 12;
}
