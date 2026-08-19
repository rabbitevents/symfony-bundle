<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

interface ContentType extends \Stringable
{
    public function getValue(): string;
}
