<?php

declare(strict_types=1);

namespace EchoFusion\EventManager;

interface EventManagerInterface
{
    public function attach(string $eventName, callable $listener, int $priority = 0): void;

    public function trigger(EventInterface $event): void;
}
