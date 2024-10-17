<?php

declare(strict_types=1);

namespace EchoFusion\EventManager\Exceptions;

use Exception;

class DuplicateListenerException extends Exception
{
    protected $message = 'The listener has already been attached to this event';
}
