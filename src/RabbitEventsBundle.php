<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle;


use RabbitEvents\Bundle\DependencyInjection\Compiler\ListenerDiscoveryPass;
use RabbitEvents\Bundle\Publisher\Registry as PublisherRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RabbitEventsBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ListenerDiscoveryPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DependencyInjection\RabbitEventsExtension();
    }

    public function boot(): void
    {
        // Use lazy provider to avoid eagerly resolving the Publisher service
        // (and thus connecting to RabbitMQ) on every request
        if ($this->container->has(Publisher::class)) {
            PublisherRegistry::setProvider(fn () => $this->container->get(Publisher::class));
        }
    }
}
