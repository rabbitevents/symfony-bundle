<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Publisher;

/**
 * Interface for events that can be published to the message broker.
 */
interface ShouldPublish
{
    /**
     * Get the event key for publishing.
     *
     * @return string
     */
    public function publishEventKey(): string;

    /**
     * Get the data to be published.
     *
     * @return mixed
     */
    public function toPublish(): mixed;
}
