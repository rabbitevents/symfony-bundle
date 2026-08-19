<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle;

use RabbitEvents\Bundle\Contract\Transport;
use RabbitEvents\Bundle\Publisher\MessageFactory;
use RabbitEvents\Bundle\Publisher\ShouldPublish;

class Publisher
{
    public function __construct(
        private MessageFactory $factory,
        private Transport $transport
    ) {
    }

    public function publish(ShouldPublish $event): void
    {
        $this->transport->send(
            $this->factory->create($event)
        );
    }
}
