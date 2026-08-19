<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Event;

use RabbitEvents\Bundle\Listener\Handler\Handler;

class ListenerHandling
{
    public function __construct(public readonly Handler $handler)
    {
    }
}
