<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

interface DelaysDelivery
{
    public function setDelay(int $delay = 0): void;
}
