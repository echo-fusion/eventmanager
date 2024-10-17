<?php

declare(strict_types=1);

namespace EchoFusion\EventManager\Tests;

use EchoFusion\EventManager\BaseEvent;
use EchoFusion\EventManager\EventInterface;
use EchoFusion\EventManager\EventManager;
use EchoFusion\EventManager\Exceptions\DuplicateListenerException;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    private EventManager $eventManager;

    protected function setUp(): void
    {
        $this->eventManager = new EventManager();
    }

    public function testAttachWithCallableListener(): void
    {
        $listener = function () {
            // Listener logic
        };

        $this->eventManager->attach('test.event', $listener);
        $this->assertTrue(true);
    }

    public function testTriggerWithNoListeners(): void
    {
        $eventManager = new EventManager();
        $eventMock = $this->createMock(EventInterface::class);

        $eventMock->method('getName')->willReturn('someEvent');

        $eventManager->trigger($eventMock);

        $this->assertTrue(true);
    }

    public function testAttachSameListenerTwice(): void
    {
        $listener = function () {
            // Do something
        };

        $this->eventManager->attach('test.event', $listener);

        $this->expectException(DuplicateListenerException::class);
        $this->eventManager->attach('test.event', $listener);
    }

    public function testTriggerEventWithSingleListener(): void
    {
        $called = 0;
        $listener = function () use (&$called) {
            $called++;
        };

        $this->eventManager->attach('test.event', $listener);

        $event = new class('test.event') extends BaseEvent {
            public function __construct(string $name)
            {
                $this->name = $name;
            }
        };

        $this->eventManager->trigger($event);
        $this->assertSame(1, $called);
    }

    public function testTriggerEventWithMultipleListeners(): void
    {
        $calls = [];

        $listener1 = function () use (&$calls) {
            $calls[] = 'listener1';
        };
        $listener2 = function () use (&$calls) {
            $calls[] = 'listener2';
        };

        $this->eventManager->attach('test.event', $listener1);
        $this->eventManager->attach('test.event', $listener2);

        $event = new class('test.event') extends BaseEvent {
            public function __construct(string $name)
            {
                $this->name = $name;
            }
        };

        $this->eventManager->trigger($event);
        $this->assertSame(['listener1', 'listener2'], $calls);
    }

    public function testEventWithParams(): void
    {
        $params = [];
        $listener = function ($event) use (&$params) {
            $params = ['key' => 'value'];
        };

        $this->eventManager->attach('test.event', $listener);

        $event = new class('test.event') extends BaseEvent {
            public function __construct(string $name)
            {
                $this->name = $name;
            }
        };

        $this->eventManager->trigger($event);
        $this->assertSame(['key' => 'value'], $params);
    }

    public function testListenerPriorityOrder(): void
    {
        $calls = [];

        $listener1 = function () use (&$calls) {
            $calls[] = 'listener1';
        };
        $listener2 = function () use (&$calls) {
            $calls[] = 'listener2';
        };

        $this->eventManager->attach('test.event', $listener1, 1);
        $this->eventManager->attach('test.event', $listener2, 2);

        $event = new class('test.event') extends BaseEvent {
            public function __construct(string $name)
            {
                $this->name = $name;
            }
        };

        $this->eventManager->trigger($event);
        $this->assertSame(['listener2', 'listener1'], $calls);
    }

    public function testStopPropagation(): void
    {
        $calls = 0;

        $listener1 = function ($event) use (&$calls) {
            $calls++;
            $event->stopPropagation();
        };
        $listener2 = function () use (&$calls) {
            $calls++;
        };

        $this->eventManager->attach('test.event', $listener1);
        $this->eventManager->attach('test.event', $listener2);

        $event = new class('test.event') extends BaseEvent {
            public function __construct(string $name)
            {
                $this->name = $name;
            }
        };

        $this->eventManager->trigger($event);
        $this->assertSame(1, $calls);
    }
}
