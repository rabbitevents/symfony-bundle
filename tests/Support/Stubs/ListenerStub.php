<?php

namespace RabbitEvents\Bundle\Tests\Support\Stubs;

class ListenerStub
{
    public function __invoke()
    {
        return func_get_args();
    }
}
