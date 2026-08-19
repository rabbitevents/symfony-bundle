<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Transport;

use RabbitEvents\Bundle\Contract\Producer;
use RabbitEvents\Bundle\Contract\Destination;
use RabbitEvents\Bundle\Contract\Transport;
use RabbitEvents\Bundle\Message\Envelope;

class Sender implements Transport
{
    public function __construct(protected Destination $destination, protected Producer $producer)
    {
    }

    public function send(Envelope $message): void
    {
        $this->producer->send($this->destination, $message->transportMessage());
    }

    public function setDelay(int $delay): void
    {
        $this->producer->setDeliveryDelay($delay * 1000);
    }
}
