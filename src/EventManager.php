<?php

declare(strict_types=1);

namespace EchoFusion\EventManager;

use EchoFusion\Contracts\EventManager\EventInterface;
use EchoFusion\Contracts\EventManager\EventManagerInterface;
use EchoFusion\EventManager\Exceptions\DuplicateListenerException;
use Psr\EventDispatcher\StoppableEventInterface;
use function array_column;
use function array_key_exists;
use function array_key_first;
use function array_splice;
use function array_values;
use function class_exists;
use function count;
use function interface_exists;
use function usort;

/**
 * A PSR-14 event dispatcher with a built-in listener provider.
 *
 * Listeners are attached under a key, which is either
 * - an event **name** (an {@see EventInterface} whose `getName()` equals the key), or
 * - the **class or interface name** of event objects (any object that is an instance of it).
 *
 * `dispatch()` calls the listeners of every key that matches the event together, highest priority first.
 */
class EventManager implements EventManagerInterface
{
    /**
     * Listeners by key.
     *
     * @var array<string, list<array{listener: callable, priority: int, sequence: int}>>
     */
    protected array $listeners = [];

    /**
     * Keys whose listeners are in order. A key is sorted when it is first used after a change.
     *
     * @var array<string, true>
     */
    private array $sorted = [];

    /**
     * Keys that name a class or an interface, matched against the type of a dispatched object.
     *
     * @var array<string, true>
     */
    private array $typeKeys = [];

    private int $sequence = 0;

    /**
     * @throws DuplicateListenerException when the same listener is already attached to the key
     */
    public function attach(string $eventName, callable $listener, int $priority = 0): void
    {
        foreach ($this->listeners[$eventName] ?? [] as $registeredListener) {
            if ($registeredListener['listener'] === $listener) {
                throw new DuplicateListenerException();
            }
        }

        $this->listeners[$eventName][] = ['listener' => $listener, 'priority' => $priority, 'sequence' => $this->sequence++];
        unset($this->sorted[$eventName]);

        if (class_exists($eventName) || interface_exists($eventName)) {
            $this->typeKeys[$eventName] = true;
        }
    }

    public function detach(string $eventName, callable $listener): bool
    {
        foreach ($this->listeners[$eventName] ?? [] as $index => $registeredListener) {
            if ($registeredListener['listener'] === $listener) {
                array_splice($this->listeners[$eventName], $index, 1);

                if ($this->listeners[$eventName] === []) {
                    unset($this->listeners[$eventName], $this->sorted[$eventName], $this->typeKeys[$eventName]);
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
     * The listeners attached under one key, in the order they are called.
     *
     * @return list<callable>
     */
    public function getListeners(string $eventName): array
    {
        return array_column($this->sortedEntries($eventName), 'listener');
    }

    /**
     * PSR-14: the listeners for an event, highest priority first, equal priorities in the order they were attached.
     *
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $keys = [];
        if ($event instanceof EventInterface && isset($this->listeners[$event->getName()])) {
            $keys[$event->getName()] = true;
        }
        foreach ($this->typeKeys as $type => $_) {
            if ($event instanceof $type) {
                $keys[$type] = true;
            }
        }

        if ($keys === []) {
            return [];
        }

        if (count($keys) === 1) {
            return array_column($this->sortedEntries((string) array_key_first($keys)), 'listener');
        }

        $entries = [];
        foreach ($keys as $key => $_) {
            $entries = [...$entries, ...$this->sortedEntries((string) $key)];
        }
        usort($entries, self::compare(...));

        return array_column($entries, 'listener');
    }

    /**
     * PSR-14: call the listeners and return the event. A stoppable event that has been stopped calls no further listener;
     * it is checked before every listener, so an event that is already stopped calls none.
     */
    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }

    /**
     * @deprecated use dispatch(); kept so code written for version 1 keeps working
     */
    public function trigger(EventInterface $event): void
    {
        $this->dispatch($event);
    }

    /**
     * @return list<array{listener: callable, priority: int, sequence: int}>
     */
    private function sortedEntries(string $key): array
    {
        if (!isset($this->listeners[$key])) {
            return [];
        }

        if (!isset($this->sorted[$key])) {
            usort($this->listeners[$key], self::compare(...));
            $this->sorted[$key] = true;
        }

        return array_values($this->listeners[$key]);
    }

    /**
     * @param array{priority: int, sequence: int} $a
     * @param array{priority: int, sequence: int} $b
     */
    private static function compare(array $a, array $b): int
    {
        return [$b['priority'], $a['sequence']] <=> [$a['priority'], $b['sequence']];
    }
}
