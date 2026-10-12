<?php

declare(strict_types=1);

namespace EchoFusion\EventManager\Tests;

use EchoFusion\Contracts\EventManager\EventInterface;
use EchoFusion\EventManager\BaseEvent;
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

    public function testListenersRunByPriorityThenByAttachOrder(): void
    {
        $order = [];
        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'low-1';
        }, -5);
        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'default-1';
        });
        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'high';
        }, 10);
        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'default-2';
        });
        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'low-2';
        }, -5);

        $this->eventManager->trigger(new BaseEvent('e'));

        $this->assertSame(['high', 'default-1', 'default-2', 'low-1', 'low-2'], $order);
    }

    public function testAttachAfterATriggerIsHonoured(): void
    {
        $order = [];
        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'b';
        });
        $this->eventManager->trigger(new BaseEvent('e'));

        $this->eventManager->attach('e', function () use (&$order) {
            $order[] = 'a';
        }, 5);
        $order = [];
        $this->eventManager->trigger(new BaseEvent('e'));

        $this->assertSame(['a', 'b'], $order);
    }

    public function testDetach(): void
    {
        $calls = 0;
        $listener = function () use (&$calls) {
            ++$calls;
        };
        $other = function () use (&$calls) {
            $calls += 10;
        };
        $this->eventManager->attach('e', $listener);
        $this->eventManager->attach('e', $other);

        $this->assertTrue($this->eventManager->detach('e', $listener));
        $this->assertFalse($this->eventManager->detach('e', $listener), 'already detached');
        $this->assertFalse($this->eventManager->detach('unknown', $listener));

        $this->eventManager->trigger(new BaseEvent('e'));
        $this->assertSame(10, $calls);

        $this->assertTrue($this->eventManager->detach('e', $other));
        $this->assertFalse($this->eventManager->hasListeners('e'), 'an event without listeners is forgotten');
    }

    public function testADetachedListenerCanBeAttachedAgain(): void
    {
        $listener = static function () {
        };
        $this->eventManager->attach('e', $listener);
        $this->eventManager->detach('e', $listener);
        $this->eventManager->attach('e', $listener);

        $this->assertSame([$listener], $this->eventManager->getListeners('e'));
    }

    public function testGetListenersAndHasListeners(): void
    {
        $a = static function () {
        };
        $b = static function () {
        };
        $this->eventManager->attach('e', $a, 1);
        $this->eventManager->attach('e', $b, 9);

        $this->assertTrue($this->eventManager->hasListeners('e'));
        $this->assertFalse($this->eventManager->hasListeners('other'));
        $this->assertSame([$b, $a], $this->eventManager->getListeners('e'));
        $this->assertSame([], $this->eventManager->getListeners('other'));
    }

    public function testAListenerCanDetachItselfWhileTheEventIsTriggered(): void
    {
        $calls = [];
        $first = null;
        $first = function () use (&$calls, &$first) {
            $calls[] = 'first';
            $this->eventManager->detach('e', $first);
        };
        $this->eventManager->attach('e', $first, 2);
        $this->eventManager->attach('e', function () use (&$calls) {
            $calls[] = 'second';
        }, 1);

        $this->eventManager->trigger(new BaseEvent('e'));
        $this->eventManager->trigger(new BaseEvent('e'));

        $this->assertSame(['first', 'second', 'second'], $calls);
    }

    public function testPropagationStopsAfterAListenerStopsIt(): void
    {
        $calls = [];
        $this->eventManager->attach('e', function (EventInterface $e) use (&$calls) {
            $calls[] = 'one';
            $e->stopPropagation();
        }, 2);
        $this->eventManager->attach('e', function () use (&$calls) {
            $calls[] = 'two';
        }, 1);

        $this->eventManager->trigger(new BaseEvent('e'));

        $this->assertSame(['one'], $calls);
    }

    public function testAnEventThatIsAlreadyStoppedCallsNoListener(): void
    {
        $called = false;
        $this->eventManager->attach('e', function () use (&$called) {
            $called = true;
        });
        $event = new BaseEvent('e');
        $event->stopPropagation();

        $this->eventManager->trigger($event);

        $this->assertFalse($called);
    }
}
