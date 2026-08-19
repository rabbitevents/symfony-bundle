<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('rabbitevents');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('connection')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('dsn')->defaultNull()->end()
            ->scalarNode('host')->defaultValue('%env(RABBITMQ_HOST)%')->end()
            ->integerNode('port')->defaultValue(5672)->end()
            ->scalarNode('user')->defaultValue('%env(RABBITMQ_USER)%')->end()
            ->scalarNode('pass')->defaultValue('%env(RABBITMQ_PASSWORD)%')->end()
            ->scalarNode('vhost')->defaultValue('/')->end()
            ->scalarNode('exchange')->defaultValue('events')->end()
            ->booleanNode('durable')->defaultTrue()->end()
            ->arrayNode('ssl')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->booleanNode('verify_peer')->defaultTrue()->end()
            ->scalarNode('cafile')->defaultNull()->end()
            ->scalarNode('local_cert')->defaultNull()->end()
            ->scalarNode('local_key')->defaultNull()->end()
            ->scalarNode('passphrase')->defaultValue('')->end()
            ->end()
            ->end()
            ->floatNode('read_timeout')->defaultValue(3.0)->end()
            ->floatNode('write_timeout')->defaultValue(3.0)->end()
            ->floatNode('connection_timeout')->defaultValue(3.0)->end()
            ->integerNode('heartbeat')->defaultValue(0)->end()
            ->booleanNode('persisted')->defaultFalse()->end()
            ->booleanNode('lazy')->defaultTrue()->end()
            ->arrayNode('qos')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('global')->defaultFalse()->end()
            ->integerNode('prefetch_size')->defaultValue(0)->end()
            ->integerNode('prefetch_count')->defaultValue(1)->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->scalarNode('default_serializer')
            ->defaultValue('RabbitEvents\\Bundle\\Message\\Serializer\\Json\\Serializer')
            ->end()
            ->arrayNode('logging')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->scalarNode('level')->defaultValue('info')->end()
            ->scalarNode('channel')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
