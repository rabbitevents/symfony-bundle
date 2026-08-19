<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\DependencyInjection;

use RabbitEvents\Bundle\Amqp\AmqpConnection;
use RabbitEvents\Bundle\Command\RabbitEventsListCommand;
use RabbitEvents\Bundle\Command\RabbitEventsListenCommand;
use RabbitEvents\Bundle\Context;
use RabbitEvents\Bundle\Contract\Connection;
use RabbitEvents\Bundle\Contract\Serializer;
use RabbitEvents\Bundle\Contract\Transport;
use RabbitEvents\Bundle\Listener\Handler\Factory as HandlerFactory;
use RabbitEvents\Bundle\Listener\Handler\Processor;
use RabbitEvents\Bundle\Listener\Registry as ListenerRegistry;
use RabbitEvents\Bundle\Listener\Worker;
use RabbitEvents\Bundle\Message\Serializer\Protobuf\Serializer as ProtobufSerializer;
use RabbitEvents\Bundle\Message\Serializer\Registry as SerializerRegistry;
use RabbitEvents\Bundle\Message\Factory as EnvelopeFactory;
use RabbitEvents\Bundle\Publisher;
use RabbitEvents\Bundle\Publisher\MessageFactory;
use RabbitEvents\Bundle\Transport\Sender;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class RabbitEventsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerConnection($container, $config['connection']);
        $this->registerSerialization($container, $config);
        $this->registerContext($container);
        $this->registerPublisher($container);
        $this->registerListener($container);
        $this->registerCommands($container);
    }

    public function getAlias(): string
    {
        return 'rabbitevents';
    }

    private function registerConnection(ContainerBuilder $container, array $connectionConfig): void
    {
        $definition = new Definition(AmqpConnection::class, [$connectionConfig]);
        $container->setDefinition(AmqpConnection::class, $definition);
        $container->setAlias(Connection::class, AmqpConnection::class);
    }

    private function registerSerialization(ContainerBuilder $container, array $config): void
    {
        // Default serializer
        $defaultSerializerClass = $config['default_serializer'];
        $container->setDefinition(Serializer::class, new Definition($defaultSerializerClass));

        // Registry with tagged serializers
        $registryDef = new Definition(SerializerRegistry::class, [
            new Reference(Serializer::class),
        ]);

        // Auto-register all serializers tagged with 'rabbitevents.serializer'
        $registryDef->addMethodCall('registerMultiple', [
            new TaggedIteratorArgument('rabbitevents.serializer')
        ]);

        $container->setDefinition(SerializerRegistry::class, $registryDef);

        // Conditionally register Protobuf serializer if google/protobuf is installed
        if (class_exists(\Google\Protobuf\Internal\Message::class)) {
            $protobufDef = new Definition(ProtobufSerializer::class);
            $protobufDef->addTag('rabbitevents.serializer');
            $container->setDefinition(ProtobufSerializer::class, $protobufDef);
        }
    }

    private function registerContext(ContainerBuilder $container): void
    {
        $definition = new Definition(Context::class, [
            new Reference(Connection::class),
            new Reference(SerializerRegistry::class),
        ]);
        $container->setDefinition(Context::class, $definition);

        // Message Factory (used by Envelope to create TransportMessages)
        $container->setDefinition(
            EnvelopeFactory::class,
            new Definition(EnvelopeFactory::class)
        );
    }

    private function registerPublisher(ContainerBuilder $container): void
    {
        // Transport (Sender) — uses a factory to defer RabbitMQ connection
        // until the Sender is actually used, respecting the lazy config option
        $senderDef = new Definition(Sender::class);
        $senderDef->setFactory([self::class, 'createSender']);
        $senderDef->addArgument(new Reference(Context::class));
        $senderDef->setLazy(true);
        $container->setDefinition(Transport::class, $senderDef);

        // Publisher MessageFactory
        $container->setDefinition(
            MessageFactory::class,
            new Definition(MessageFactory::class, [new Reference(SerializerRegistry::class)])
        );

        // Publisher
        $publisherDef = new Definition(Publisher::class, [
            new Reference(MessageFactory::class),
            new Reference(Transport::class),
        ]);
        $publisherDef->setPublic(true);
        $container->setDefinition(Publisher::class, $publisherDef);
    }

    private function registerListener(ContainerBuilder $container): void
    {
        // ListenerRegistry (RabbitEvents-specific registry for routing-key listeners)
        $container->setDefinition(
            ListenerRegistry::class,
            (new Definition(ListenerRegistry::class))->setPublic(true)
        );

        // HandlerFactory
        $container->setDefinition(
            HandlerFactory::class,
            new Definition(HandlerFactory::class, [
                new Reference(ListenerRegistry::class),
                new Reference(Transport::class),
            ])
        );

        // Processor
        $container->setDefinition(
            Processor::class,
            new Definition(Processor::class, [
                new Reference(HandlerFactory::class),
                new Reference('event_dispatcher'),
                new Reference('logger'),
            ])
        );

        // Worker
        $container->setDefinition(
            Worker::class,
            new Definition(Worker::class, [
                new Reference('logger'),
                new Reference('event_dispatcher'),
            ])
        );
    }

    private function registerCommands(ContainerBuilder $container): void
    {
        $listenDef = new Definition(RabbitEventsListenCommand::class, [
            new Reference(Context::class),
            new Reference(Worker::class),
            new Reference(Processor::class),
        ]);
        $listenDef->addTag('console.command');
        $container->setDefinition(RabbitEventsListenCommand::class, $listenDef);

        $listDef = new Definition(RabbitEventsListCommand::class, [
            new Reference(ListenerRegistry::class),
        ]);
        $listDef->addTag('console.command');
        $container->setDefinition(RabbitEventsListCommand::class, $listDef);
    }

    public static function createSender(Context $context): Sender
    {
        $topic = $context->createTopic();
        $producer = $context->createProducer();

        return new Sender($topic, $producer);
    }
}
