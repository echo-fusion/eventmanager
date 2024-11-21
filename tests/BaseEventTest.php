<?php

declare(strict_types=1);

namespace EchoFusion\EventManager\Tests;

use EchoFusion\EventManager\BaseEvent;
use PHPUnit\Framework\TestCase;

class BaseEventTest extends TestCase
{
    public function testBaseEventNameAndParams()
    {
        $event = new BaseEvent('test.event', ['key' => 'value']);

        $this->assertEquals('test.event', $event->getName());
        $this->assertEquals(['key' => 'value'], $event->getParams());
    }

    public function testStopPropagation()
    {
        $event = new BaseEvent('test.event');

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testPropagationFlag()
    {
        $event = new BaseEvent('test.event');

        // Initially, propagation is not stopped
        $this->assertFalse($event->isPropagationStopped());

        // Stop propagation and verify
        $event->stopPropagation(true);
        $this->assertTrue($event->isPropagationStopped());

        // Restart propagation and verify
        $event->stopPropagation(false);
        $this->assertFalse($event->isPropagationStopped());
    }
}
