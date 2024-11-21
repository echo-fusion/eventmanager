<?php

declare(strict_types=1);

namespace EchoFusion\EventManager;

use EchoFusion\Contracts\EventManager\EventInterface;
use EchoFusion\Contracts\EventManager\EventManagerInterface;
use EchoFusion\EventManager\Exceptions\DuplicateListenerException;

class EventManager implements EventManagerInterface
{
    protected array $listeners = [];

    public function attach(string $eventName, callable $listener, int $priority = 0): void
    {
        // Check if the listener is already attached
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $registeredListener) {
                // Check for duplicate listener
                if ($registeredListener['listener'] === $listener) {
                    throw new DuplicateListenerException();
                }
            }
        }

        // Ensure we're adding a callable listener with its priority
        $this->listeners[$eventName][] = ['listener' => $listener, 'priority' => $priority];

        // Sort listeners by priority (higher priority first)
        usort($this->listeners[$eventName], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    public function trigger(EventInterface $event): void
    {
        $eventName = $event->getName();
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listenerData) {
            $listener = $listenerData['listener'];
            $listener($event);

            if ($event->isPropagationStopped()) {
                return;
            }
        }
    }
}
