<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener;

use RabbitEvents\Bundle\Contract\DelaysDelivery;
use RabbitEvents\Bundle\Contract\Destination;
use RabbitEvents\Bundle\Contract\Producer;
use RabbitEvents\Bundle\Transport\Sender;

class Releaser extends Sender implements DelaysDelivery
{
    public function __construct(Destination $destination, Producer $producer)
    {
        parent::__construct($destination, $producer);
    }

    public function setDelay(int $delay = 0): void
    {
        $this->producer->setDeliveryDelay($delay * 1000); // Convert seconds to milliseconds
    }
}
