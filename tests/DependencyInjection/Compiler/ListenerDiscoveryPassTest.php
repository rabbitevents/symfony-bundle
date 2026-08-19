<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Tests\DependencyInjection\Compiler;

use RabbitEvents\Bundle\DependencyInjection\Compiler\ListenerDiscoveryPass;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;
use RabbitEvents\Bundle\Listener\Registry as ListenerRegistry;
use RabbitEvents\Bundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ListenerDiscoveryPassTest extends TestCase
{
    public function testDiscoversClassAndMethodAttributes(): void
    {
        $container = new ContainerBuilder();
        $registryDef = new Definition(ListenerRegistry::class);
        $container->setDefinition(ListenerRegistry::class, $registryDef);

        $serviceDef = new Definition(DummyListenerService::class);
        $container->setDefinition('dummy_service', $serviceDef);

        $pass = new ListenerDiscoveryPass();
        $pass->process($container);

        $methodCalls = $registryDef->getMethodCalls();

        // Expect 2 calls: one for class-level 'order.created', one for method-level 'order.cancelled'
        self::assertCount(2, $methodCalls);

        self::assertSame('listen', $methodCalls[0][0]);
        self::assertSame('order.created', $methodCalls[0][1][0]);
        self::assertEquals(new Reference('dummy_service'), $methodCalls[0][1][1]);

        self::assertSame('listen', $methodCalls[1][0]);
        self::assertSame('order.cancelled', $methodCalls[1][1][0]);
        self::assertEquals([new Reference('dummy_service'), 'onCancelled'], $methodCalls[1][1][1]);
    }
}

#[AsRabbitListener(event: 'order.created')]
class DummyListenerService
{
    public function handle(array $payload): void
    {
    }

    #[AsRabbitListener(event: 'order.cancelled')]
    public function onCancelled(array $payload): void
    {
    }
}
