<?php

namespace RabbitEvents\Bundle\Tests\Support\Stubs;

class ListenerStubForMiddleware
{
    public function __invoke()
    {
        return func_get_args();
    }
}
