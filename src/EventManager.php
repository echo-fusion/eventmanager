<?php

declare(strict_types=1);

namespace EchoFusion\EventManager;

use EchoFusion\Contracts\EventManager\EventInterface;
use EchoFusion\Contracts\EventManager\EventManagerInterface;
use EchoFusion\EventManager\Exceptions\DuplicateListenerException;
use function array_column;
use function array_key_exists;
use function array_splice;
use function usort;

class EventManager implements EventManagerInterface
{
    /**
     * Listeners by event name.
     *
     * @var array<string, list<array{listener: callable, priority: int}>>
     */
    protected array $listeners = [];

    /**
     * Event names whose listeners are in priority order. A name is sorted when it is first used after a change,
     * not on every attach().
     *
     * @var array<string, true>
     */
    private array $sorted = [];

    /**
     * @throws DuplicateListenerException when the same listener is already attached to the event
     */
    public function attach(string $eventName, callable $listener, int $priority = 0): void
    {
        foreach ($this->listeners[$eventName] ?? [] as $registeredListener) {
            if ($registeredListener['listener'] === $listener) {
                throw new DuplicateListenerException();
            }
        }

        $this->listeners[$eventName][] = ['listener' => $listener, 'priority' => $priority];
        unset($this->sorted[$eventName]);
    }

    /**
     * Remove a listener (the same callable that was attached).
     *
     * @return bool whether the listener was attached
     */
    public function detach(string $eventName, callable $listener): bool
    {
        foreach ($this->listeners[$eventName] ?? [] as $index => $registeredListener) {
            if ($registeredListener['listener'] === $listener) {
                array_splice($this->listeners[$eventName], $index, 1);

                if ($this->listeners[$eventName] === []) {
                    unset($this->listeners[$eventName], $this->sorted[$eventName]);
                }

                return true;
            }
        }

        return false;
    }

    public function hasListeners(string $eventName): bool
    {
        return array_key_exists($eventName, $this->listeners);
    }

    /**
     * The listeners of an event in the order they are called: higher priority first, attach order for equal priorities.
     *
     * @return list<callable>
     */
    public function getListeners(string $eventName): array
    {
        return array_column($this->sortedListeners($eventName), 'listener');
    }

    /**
     * Call the listeners of the event, highest priority first. Propagation stops as soon as the event says so;
     * it is checked before every listener, so an event that is already stopped calls none.
     */
    public function trigger(EventInterface $event): void
    {
        foreach ($this->sortedListeners($event->getName()) as $listenerData) {
            if ($event->isPropagationStopped()) {
                return;
            }

            ($listenerData['listener'])($event);
        }
    }

    /**
     * @return list<array{listener: callable, priority: int}>
     */
    private function sortedListeners(string $eventName): array
    {
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        if (!isset($this->sorted[$eventName])) {
            // sorting is stable since PHP 8.0, so equal priorities keep the order they were attached in
            usort($this->listeners[$eventName], static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);
            $this->sorted[$eventName] = true;
        }

        return $this->listeners[$eventName];
    }
}
