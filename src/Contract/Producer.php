<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

interface Producer
{
    /**
     * Send a message to the destination.
     *
     * @param Destination $destination
     * @param TransportMessage $message
     * @return void
     */
    public function send(Destination $destination, TransportMessage $message): void;

    /**
     * Set delivery delay in milliseconds.
     *
     * @param int|null $deliveryDelay
     * @return void
     */
    public function setDeliveryDelay(?int $deliveryDelay = null): void;
}
