<?php

declare(strict_types=1);

namespace EchoFusion\EventManager\Tests;

use EchoFusion\Contracts\EventManager\EventInterface;
use EchoFusion\Contracts\EventManager\EventManagerInterface;
use EchoFusion\EventManager\BaseEvent;
use EchoFusion\EventManager\EventManager;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use stdClass;

interface Auditable
{
}

final class UserRegistered implements Auditable
{
    public function __construct(public readonly string $email)
    {
    }
}

final class Stoppable implements StoppableEventInterface
{
    public bool $stopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}

final class Psr14Test extends TestCase
{
    private EventManager $events;

    protected function setUp(): void
    {
        $this->events = new EventManager();
    }

    public function testItIsAPsr14DispatcherAndListenerProvider(): void
    {
        $this->assertInstanceOf(EventManagerInterface::class, $this->events);
        $this->assertInstanceOf(EventDispatcherInterface::class, $this->events);
        $this->assertInstanceOf(ListenerProviderInterface::class, $this->events);
        $this->assertInstanceOf(StoppableEventInterface::class, new BaseEvent('x'));
    }

    public function testPlainObjectsAreDispatchedByTheirClass(): void
    {
        $seen = [];
        $this->events->attach(UserRegistered::class, function (UserRegistered $event) use (&$seen) {
            $seen[] = $event->email;
        });

        $event = new UserRegistered('ann@example.com');
        $returned = $this->events->dispatch($event);

        $this->assertSame($event, $returned, 'dispatch() returns the event');
        $this->assertSame(['ann@example.com'], $seen);
    }

    public function testListenersCanBeAttachedToAnInterface(): void
    {
        $calls = 0;
        $this->events->attach(Auditable::class, function () use (&$calls) {
            ++$calls;
        });

        $this->events->dispatch(new UserRegistered('a@b.c'));
        $this->events->dispatch(new stdClass());

        $this->assertSame(1, $calls);
    }

    public function testNamedEventsAndClassKeysAreCalledTogetherByPriority(): void
    {
        $order = [];
        $this->events->attach('user.registered', function () use (&$order) {
            $order[] = 'by-name';
        }, 5);
        $this->events->attach(NamedUserEvent::class, function () use (&$order) {
            $order[] = 'by-class-high';
        }, 10);
        $this->events->attach(EventInterface::class, function () use (&$order) {
            $order[] = 'by-interface-low';
        }, -1);
        $this->events->attach(NamedUserEvent::class, function () use (&$order) {
            $order[] = 'by-class-default';
        });

        $this->events->dispatch(new NamedUserEvent('user.registered'));

        $this->assertSame(['by-class-high', 'by-name', 'by-class-default', 'by-interface-low'], $order);
    }

    public function testAKeyThatIsBothTheNameAndTheClassIsCalledOnce(): void
    {
        $calls = 0;
        $this->events->attach(NamedUserEvent::class, function () use (&$calls) {
            ++$calls;
        });

        $this->events->dispatch(new NamedUserEvent(NamedUserEvent::class));

        $this->assertSame(1, $calls);
    }

    public function testStoppableEventsStopPropagation(): void
    {
        $calls = [];
        $this->events->attach(Stoppable::class, function (Stoppable $e) use (&$calls) {
            $calls[] = 'first';
            $e->stopped = true;
        }, 2);
        $this->events->attach(Stoppable::class, function () use (&$calls) {
            $calls[] = 'second';
        }, 1);

        $this->events->dispatch(new Stoppable());

        $this->assertSame(['first'], $calls);
    }

    public function testEventsThatAreNotStoppableRunEveryListener(): void
    {
        $calls = 0;
        $this->events->attach(UserRegistered::class, function () use (&$calls) {
            ++$calls;
        });
        $this->events->attach(UserRegistered::class, function () use (&$calls) {
            $calls += 10;
        });

        $this->events->dispatch(new UserRegistered('x@y.z'));

        $this->assertSame(11, $calls);
    }

    public function testTheListenerProviderReturnsListenersInCallOrder(): void
    {
        $a = static function () {
        };
        $b = static function () {
        };
        $this->events->attach(UserRegistered::class, $a, 1);
        $this->events->attach(Auditable::class, $b, 2);

        $this->assertSame([$b, $a], [...$this->events->getListenersForEvent(new UserRegistered('a@b.c'))]);
        $this->assertSame([], [...$this->events->getListenersForEvent(new stdClass())]);
    }

    public function testDetachingStopsClassKeyedListenersToo(): void
    {
        $calls = 0;
        $listener = function () use (&$calls) {
            ++$calls;
        };
        $this->events->attach(UserRegistered::class, $listener);
        $this->events->dispatch(new UserRegistered('a@b.c'));

        $this->assertTrue($this->events->detach(UserRegistered::class, $listener));
        $this->events->dispatch(new UserRegistered('a@b.c'));

        $this->assertSame(1, $calls);
    }

    public function testTriggerStillWorksAsAnAliasOfDispatch(): void
    {
        $calls = 0;
        $this->events->attach('legacy', function () use (&$calls) {
            ++$calls;
        });

        $this->events->trigger(new BaseEvent('legacy'));

        $this->assertSame(1, $calls);
    }
}

final class NamedUserEvent extends BaseEvent
{
}
