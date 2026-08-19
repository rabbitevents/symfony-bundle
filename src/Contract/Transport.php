<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

use RabbitEvents\Bundle\Message\Envelope;

interface Transport
{
    /**
     * @param Envelope $message
     */
    public function send(Envelope $message): void;

    /**
     * Set delay in seconds.
     *
     * @param int $delay
     */
    public function setDelay(int $delay): void;
}
