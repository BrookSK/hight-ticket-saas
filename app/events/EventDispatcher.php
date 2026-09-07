<?php

declare(strict_types=1);

namespace App\Events;

/**
 * Event dispatcher.
 *
 * Decouples features: a producer dispatches a named event and any number of
 * listeners react to it, without direct coupling. Listeners are callables (or
 * invokable classes) registered per event name.
 *
 * Prepared so that, in the future, listeners may be queued (jobs) instead of
 * running synchronously.
 */
final class EventDispatcher
{
    /** @var array<string, list<callable>> */
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event to all listeners.
     *
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload, $event);
        }
    }
}
