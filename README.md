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

## Testing

```bash
composer test
```

## Credits

- [Amir Shadanfar](https://github.com/amir-shadanfar)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
