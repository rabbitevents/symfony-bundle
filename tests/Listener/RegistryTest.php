<?php declare(strict_types=1);

namespace RabbitEvents\Bundle\Tests\Listener;

use RabbitEvents\Bundle\Listener\Registry;
use RabbitEvents\Bundle\Tests\Support\Stubs\ListenerStub;
use RabbitEvents\Bundle\Tests\Support\Stubs\ListenerStubForMiddleware;
use RabbitEvents\Bundle\Tests\TestCase;

class RegistryTest extends TestCase
{
    private array $listen = [
        'item.created' => [
            'Listeners/Class1',
            'Listeners/Class2',
        ],
        'item.updated' => [
            'Listeners/Class3'
        ],
        'item.*' => [
            'Listeners/Class4'
        ]
    ];

    public function testGetEvents(): void
    {
        $events = array_keys($this->listen);

        self::assertEquals($events, $this->setupDispatcher()->getEvents());
    }

    public function testListen(): void
    {
        $dispatcher = new Registry();
        $dispatcher->listen('item.event', static function () {});

        self::assertTrue($dispatcher->hasListeners('item.event'));
    }

    public function testAddedClosureListeners(): void
    {
        $dispatcher = new Registry();
        $closure1 = static function () {};
        $closure2 = static function () {};

        $dispatcher->listen('item.event', $closure1);
        $dispatcher->listen('item.event', $closure2);

        $listeners = $dispatcher->getListeners('item.event');

        self::assertCount(2, $listeners);
    }

    public function testSimpleListenerCallWithAssocArrayAsPayload(): void
    {
        $payload = ['item' => true];

        $dispatcher = new Registry();
        $dispatcher->listen('simple', ListenerStub::class);
        $listeners = $dispatcher->getListeners('simple');
        $closure = reset($listeners);

        // Array because listener returns func_get_args
        $this->assertEquals([$payload], $closure('simple', $payload));
    }

    public function testWildcardListenerCallWithAssocArrayAsPayload(): void
    {
        $payload = ['item' => true];

        $dispatcher = new Registry();
        $dispatcher->listen('wildcard.*', ListenerStub::class);
        $listeners = $dispatcher->getListeners('wildcard.event');
        $closure = reset($listeners);

        // Array is because listener returns func_get_args
        $this->assertEquals(['wildcard.event', $payload], $closure('wildcard.event', $payload));
    }

    public function testGetListeners()
    {
        $dispatcher = $this->setupDispatcher();

        $preparedListeners = $dispatcher->getListeners('item.created');

        // item.created has 2 direct + 1 wildcard = 3
        self::assertCount(3, $preparedListeners);

        foreach ($preparedListeners as $listener) {
            self::assertIsCallable($listener);
        }
    }

    public function testAddListenerWhichIsAnObject()
    {
        $dispatcher = new Registry();
        $dispatcher->listen('some.event', new ListenerStubForMiddleware());

        $listeners = $dispatcher->getListeners('some.event');

        $callback = array_shift($listeners);

        $payload = ['pay' => 'load'];
        $result = $callback('some.event', $payload);

        self::assertEquals($payload, array_shift($result));
    }

    public function testListenerCallWithObjectAsPayload(): void
    {
        $payload = new \stdClass();
        $payload->item = true;

        $dispatcher = new Registry();
        $dispatcher->listen('simple', ListenerStub::class);
        $listeners = $dispatcher->getListeners('simple');
        $closure = reset($listeners);

        $result = $closure('simple', $payload);

        $this->assertEquals([$payload], $result);
    }

    private function setupDispatcher(): Registry
    {
        $dispatcher = new Registry();

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }

        return $dispatcher;
    }
}
