<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Amqp;

use Enqueue\AmqpTools\DelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\Impl\AmqpBind;
use RabbitEvents\Bundle\Contract\Connection as ConnectionContract;
use RabbitEvents\Bundle\Contract\Destination;
use RabbitEvents\Bundle\Contract\Producer;
use RabbitEvents\Bundle\Contract\QueueConsumer;

class AmqpConnection implements ConnectionContract
{
    private array $config;

    /**
     * @var DelayStrategy
     */
    private $delayStrategy;

    /**
     * @var AmqpConnectionFactory
     */
    private $connection;

    /**
     * @var AmqpContext
     */
    private $context;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return AmqpConnectionFactory
     */
    public function connect(): AmqpConnectionFactory
    {
        if (!$this->connection) {
            $this->connection = $this->factory();
        }

        return $this->connection;
    }

    /**
     * @return AmqpContext
     */
    public function createContext(): AmqpContext
    {
        if (!$this->context) {
            /** @var AmqpContext $context */
            $context = $this->connect()->createContext();
            $this->context = $context;
        }

        return $this->context;
    }

    public function createProducer(): Producer
    {
        /** @var AmqpProducer $producer */
        $producer = $this->createContext()->createProducer();

        return new AmqpProducerAdapter($producer);
    }

    public function createConsumer(Destination $queue): QueueConsumer
    {
        return new AmqpConsumerAdapter($this->createContext()->createConsumer($queue->getOrigin()));
    }

    public function createTopic(): Destination
    {
        $topic = (new DestinationTopicFactory($this->createContext(), $this))
            ->createAndDeclare($this->getConfig('exchange'));

        return new AmqpDestinationAdapter($topic);
    }

    public function createQueue(string $queueName, array $events, Destination $topic): Destination
    {
        $queue = (new QueueFactory($this->createContext(), $this))->createAndDeclare($queueName);

        foreach ($events as $event) {
            $this->createContext()->bind(new AmqpBind($topic->getOrigin(), $queue, $event));
        }

        return new AmqpDestinationAdapter($queue);
    }

    /**
     * @param DelayStrategy $strategy
     * @return $this
     */
    public function setDelayStrategy(DelayStrategy $strategy): self
    {
        $this->delayStrategy = $strategy;

        return $this;
    }

    /**
     * @return DelayStrategy
     */
    public function getDelayStrategy(): DelayStrategy
    {
        if (!$this->delayStrategy) {
            $class = $this->getConfig('delay_strategy', RabbitMqDlxDelayStrategy::class);

            $this->delayStrategy = new $class();
        }

        return $this->delayStrategy;
    }

    public function getConfig($key = null, $default = null): mixed
    {
        if (!is_null($key)) {
            return $this->config[$key] ?? $default;
        }

        return $this->config;
    }

    /**
     * @return AmqpConnectionFactory
     */
    protected function factory(): AmqpConnectionFactory
    {
        $connectionFactoryClass = $this->getConnectionFactoryClass();

        $factory = new $connectionFactoryClass([
            'dsn' => $this->getConfig('dsn'),
            'host' => $this->getConfig('host', '127.0.0.1'),
            'port' => $this->getConfig('port', 5672),
            'user' => $this->getConfig('user', 'guest'),
            'pass' => $this->getConfig('pass', 'guest'),
            'vhost' => $this->getConfig('vhost', '/'),
            'ssl_on' => $this->getSslConfig('enabled', false),
            'ssl_verify' => $this->getSslConfig('verify_peer', true),
            'ssl_cacert' => $this->getSslConfig('cafile'),
            'ssl_cert' => $this->getSslConfig('local_cert'),
            'ssl_key' => $this->getSslConfig('local_key'),
            'ssl_passphrase' => $this->getSslConfig('passphrase', ''),
            'read_timeout' => $this->getConfig('read_timeout', 3.),
            'write_timeout' => $this->getConfig('write_timeout', 3.),
            'connection_timeout' => $this->getConfig('connection_timeout', 3.),
            'heartbeat' => $this->getConfig('heartbeat', 0),
            'persisted' => $this->getConfig('persisted', false),
            'lazy' => $this->getConfig('lazy', true),
            'qos_global' => $this->getQosConfig('global', false),
            'qos_prefetch_size' => $this->getQosConfig('prefetch_size', 0),
            'qos_prefetch_count' => $this->getQosConfig('prefetch_count', 1),
        ]);

        $factory->setDelayStrategy($this->getDelayStrategy());

        return $factory;
    }

    private function getSslConfig(string $key, mixed $default = null): mixed
    {
        return $this->config['ssl'][$key] ?? $default;
    }

    private function getQosConfig(string $key, mixed $default = null): mixed
    {
        return $this->config['qos'][$key] ?? $default;
    }

    private function getConnectionFactoryClass(): string
    {
        if (extension_loaded('amqp') && class_exists('Enqueue\AmqpExt\AmqpConnectionFactory')) {
            return \Enqueue\AmqpExt\AmqpConnectionFactory::class;
        } else {
            return \Enqueue\AmqpLib\AmqpConnectionFactory::class;
        }
    }
}
