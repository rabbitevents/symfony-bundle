<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\DependencyInjection\Compiler;

use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;
use RabbitEvents\Bundle\Listener\Registry as ListenerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ListenerDiscoveryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ListenerRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(ListenerRegistry::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (!$class || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            // Register class-level attributes
            foreach ($reflection->getAttributes(AsRabbitListener::class) as $attribute) {
                /** @var AsRabbitListener $listener */
                $listener = $attribute->newInstance();

                $registry->addMethodCall('listen', [
                    $listener->event,
                    new Reference($id),
                ]);
            }

            // Register method-level attributes
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(AsRabbitListener::class) as $attribute) {
                    /** @var AsRabbitListener $listener */
                    $listener = $attribute->newInstance();

                    $registry->addMethodCall('listen', [
                        $listener->event,
                        [new Reference($id), $method->getName()],
                    ]);
                }
            }
        }
    }
}
