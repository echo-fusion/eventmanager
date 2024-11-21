# EventManager

EventManager is a flexible and lightweight event handling system for PHP, allowing you to attach and trigger events with priorities and listeners.


## Installation

Install the package via Composer:

```bash
composer require echo-fusion/eventmanager
```

## Requirements

The following versions of PHP are supported by this version.

* PHP 8.1
* PHP 8.2
* PHP 8.3

## Usage

Here’s how to use the PluginManager to set up and run:

```php
use EchoFusion\EventManager\EventManager;
use EchoFusion\Contracts\EventManager\EventManagerInterface;

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
```php
Listener 1: The event 'user.register' was triggered!
Listener 2: The event 'user.register' was triggered!
```

## Testing

Testing includes PHPUnit and PHPStan (Level 7).

``` bash
$ composer test
```

## Credits
Developed and maintained by [Amir Shadanfar](https://github.com/amir-shadanfar).  
Connect on [LinkedIn](https://www.linkedin.com/in/amir-shadanfar).

## License

The MIT License (MIT). Please see [License File](https://github.com/echo-fusion/middlewaremanager/blob/main/LICENSE) for more information.

