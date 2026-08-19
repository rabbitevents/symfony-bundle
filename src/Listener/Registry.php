<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener;

/**
 * Dispatches events to registered RabbitEvents listeners.
 * This is NOT Symfony's EventDispatcher—it manages routing-key-based
 * listeners for messages consumed from RabbitMQ.
 */
class Registry
{
    /**
     * @var array<string, array<mixed>>
     */
    protected array $listeners = [];

    /**
     * @var array<string, array<mixed>>
     */
    protected array $wildcards = [];

    /**
     * Register a listener for an event.
     *
     * @param string|array $events
     * @param mixed $listener
     */
    public function listen(string|array $events, mixed $listener = null): void
    {
        foreach ((array) $events as $event) {
            if ($this->isDuplicateListener($event, $listener)) {
                return;
            }

            if (str_contains($event, '*')) {
                $this->wildcards[$event][] = $this->makeListener($listener, true);
            } else {
                $this->listeners[$event][] = $this->makeListener($listener);
            }
        }
    }

    /**
     * Get all registered event names.
     *
     * @return array
     */
    public function getEvents(): array
    {
        return array_unique(array_merge(
            array_keys($this->listeners),
            array_keys($this->wildcards)
        ));
    }

    /**
     * Get listeners for a specific event.
     *
     * @param string $event
     * @return array
     */
    public function getListeners(string $event): array
    {
        $listeners = $this->listeners[$event] ?? [];

        // Add wildcard listeners that match this event
        foreach ($this->wildcards as $pattern => $wildcardListeners) {
            if ($this->eventMatchesPattern($event, $pattern)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        return $listeners;
    }

    /**
     * Check if there are listeners for the given event.
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->getListeners($event));
    }

    /**
     * Determine if the given event matches the pattern.
     */
    protected function eventMatchesPattern(string $event, string $pattern): bool
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^' . $pattern . '$#u', $event);
    }

    /**
     * Make a listener closure.
     */
    protected function makeListener(mixed $listener, bool $wildcard = false): callable
    {
        if ($listener instanceof \Closure) {
            return $listener;
        }

        if (is_object($listener)) {
            return function (string $event, mixed $payload) use ($listener) {
                if (method_exists($listener, 'handle')) {
                    return $listener->handle($payload);
                }

                return $listener($payload);
            };
        }

        if (is_array($listener)) {
            return function (string $event, mixed $payload) use ($listener, $wildcard) {
                if ($wildcard) {
                    return $this->callListener($listener, $event, $payload);
                }

                return $this->callListener($listener, $payload);
            };
        }

        // String class name — create a closure that instantiates and calls handle()
        return function (string $event, mixed $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $this->callListener($listener, $event, $payload);
            }

            return $this->callListener($listener, $payload);
        };
    }

    /**
     * @param mixed $listener Class name, array callable or invokable
     * @param mixed ...$args
     * @return mixed
     */
    protected function callListener(mixed $listener, mixed ...$args): mixed
    {
        if (is_array($listener)) {
            [$target, $method] = $listener;
            if (is_string($target) && class_exists($target)) {
                $target = new $target();
            }

            return $target->{$method}(...$args);
        }

        if (is_string($listener) && class_exists($listener)) {
            $instance = new $listener();

            if (method_exists($instance, 'handle')) {
                return $instance->handle(...$args);
            }

            if (is_callable($instance)) {
                return $instance(...$args);
            }
        }

        return call_user_func($listener, ...$args);
    }

    /**
     * Determine if the given listener is a duplicate.
     */
    protected function isDuplicateListener(string $event, mixed $listener): bool
    {
        $registered = str_contains($event, '*')
            ? ($this->wildcards[$event] ?? [])
            : ($this->listeners[$event] ?? []);

        if (empty($registered)) {
            return false;
        }

        $listenerClass = $this->extractListenerClass($listener);

        if (!$listenerClass) {
            return false;
        }

        return in_array($listenerClass, array_map(
            fn(mixed $l) => $this->extractListenerClass($l),
            $registered
        ));
    }

    /**
     * Extract the class name from a listener definition.
     */
    protected function extractListenerClass(mixed $listener): ?string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return $listener[0];
        }

        if (is_object($listener) && !$listener instanceof \Closure) {
            return get_class($listener);
        }

        return null;
    }
}
