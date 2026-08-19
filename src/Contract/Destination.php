<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

interface Destination
{
    /**
     * Get the original underlying destination object.
     *
     * @return mixed
     */
    public function getOrigin(): mixed;
}
