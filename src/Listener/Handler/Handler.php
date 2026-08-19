<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Handler;

use RabbitEvents\Bundle\Contract\Transport;
use RabbitEvents\Bundle\Exception\FailedException;
use RabbitEvents\Bundle\Message\Envelope;

class Handler
{
    protected bool $released = false;
    protected bool $failed = false;

    /**
     * @var \Closure|null
     */
    protected ?\Closure $failedCallback = null;

    public function __construct(
        protected string $listenerClass,
        protected mixed $listener,
        protected Envelope $message,
        protected ?Transport $releaser = null
    ) {
    }

    public function handle(): mixed
    {
        $payload = $this->message->payload->value();

        // Array callable [service, method]
        if (is_array($this->listener)) {
            return $this->listener[0]->{$this->listener[1]}($payload);
        }

        // Closure — gets event name and payload
        if ($this->listener instanceof \Closure) {
            return ($this->listener)($this->message->event, $payload);
        }

        // Service object with handle() method
        if (is_object($this->listener) && method_exists($this->listener, 'handle')) {
            return $this->listener->handle($payload);
        }

        // Callable object (__invoke)
        if (is_callable($this->listener)) {
            return ($this->listener)($payload);
        }

        throw new \RuntimeException(sprintf('Listener of type "%s" is not callable', get_debug_type($this->listener)));
    }

    public function getPayload(): mixed
    {
        return $this->message->payload->value();
    }

    public function getMessage(): Envelope
    {
        return $this->message;
    }

    public function getListenerClass(): string
    {
        return $this->listenerClass;
    }

    public function release(int $delay = 0): void
    {
        $this->released = true;

        if ($this->releaser) {
            if ($delay > 0) {
                $this->releaser->setDelay($delay);
            }

            $this->releaser->send($this->message);
        }
    }

    public function isReleased(): bool
    {
        return $this->released;
    }

    public function fail(\Throwable $e): void
    {
        $this->failed = true;

        if ($this->failedCallback) {
            ($this->failedCallback)($e);
        }
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function setFailedCallback(\Closure $callback): self
    {
        $this->failedCallback = $callback;

        return $this;
    }
}
