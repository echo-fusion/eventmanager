# EventManager

[![CI](https://github.com/echo-fusion/eventmanager/actions/workflows/ci.yml/badge.svg)](https://github.com/echo-fusion/eventmanager/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/v/echo-fusion/eventmanager.svg)](https://packagist.org/packages/echo-fusion/eventmanager) [![PHP](https://img.shields.io/badge/php-8.1%20%7C%208.2%20%7C%208.3-777bb4.svg)](https://www.php.net) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

EventManager is a flexible and lightweight event handling system for PHP, allowing you to attach and trigger events with priorities and listeners.

> Part of [EchoFusion](https://github.com/echo-fusion), a modular PHP framework built from small packages.

## Installation

```bash
composer require echo-fusion/eventmanager
```

## Requirements

* PHP 8.1, 8.2 or 8.3

## Usage

Here’s how to use the EventManager to attach listeners and trigger an event:

```php
use EchoFusion\Contracts\EventManager\EventInterface;
use EchoFusion\EventManager\BaseEvent;
use EchoFusion\EventManager\EventManager;

// Create an instance of EventManager
$eventManager = new EventManager();

// Define the first listener
$listener1 = function (EventInterface $event) {
    echo "Listener 1: The event '" . $event->getName() . "' was triggered!\n";
};

// Define the second listener
$listener2 = function (EventInterface $event) {
    echo "Listener 2: The event '" . $event->getName() . "' was triggered!\n";
};

// Attach both listeners to an event named 'user.register'
$eventManager->attach('user.register', $listener1);
$eventManager->attach('user.register', $listener2);

// Create a new event
$event = new class('user.register') extends BaseEvent {
    public function __construct(string $name)
    {
        $this->setName($name);
    }
};

// Trigger the 'user.register' event, and both listeners will respond
$eventManager->trigger($event);
```
Output:
```text
Listener 1: The event 'user.register' was triggered!
Listener 2: The event 'user.register' was triggered!
```

## PSR-14

`EventManager` is a [PSR-14](https://www.php-fig.org/psr/psr-14/) event dispatcher **and** listener provider, so it can be used wherever a `Psr\EventDispatcher\EventDispatcherInterface` is expected, and replaced by any other PSR-14 dispatcher (such as Symfony's) without changing the code that dispatches.

```php
final class UserRegistered
{
    public function __construct(public readonly string $email) {}
}

$eventManager->attach(UserRegistered::class, function (UserRegistered $event) {
    // send the welcome mail to $event->email
});

$eventManager->dispatch(new UserRegistered('ann@example.com'));   // returns the event
```

A listener key is either an **event name** (for `EventInterface` events such as `BaseEvent('user.register')`) or the **class or interface name** of event objects; the listeners of every key that matches an event run together, highest priority first. An event that implements `Psr\EventDispatcher\StoppableEventInterface` (every `EventInterface` does) can stop propagation.

`trigger($event)` still works as a deprecated alias of `dispatch($event)`.

## Priorities, detaching and inspecting

```php
$eventManager->attach('user.register', $sendWelcomeMail, 10);   // higher priority runs first; default 0
$eventManager->attach('user.register', $audit, -10);            // runs last; equal priorities keep attach order

$eventManager->detach('user.register', $audit);                 // true when it was attached (same callable)
$eventManager->hasListeners('user.register');                   // bool
$eventManager->getListeners('user.register');                   // callables attached under that key, in call order
```

* Listeners are sorted once per change, when the event is next triggered, not on every `attach()`.
* Attaching the same callable twice to an event throws `DuplicateListenerException`.
* `trigger()` checks `isPropagationStopped()` **before** each listener, so a listener that calls `$event->stopPropagation()` skips the rest, and an event that is already stopped calls none.
* A listener may detach itself (or another) while the event is being triggered.

## Testing

```bash
composer test
```

## Credits

- [Amir Shadanfar](https://github.com/amir-shadanfar)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
