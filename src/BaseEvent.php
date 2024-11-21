<?php

declare(strict_types=1);

namespace EchoFusion\EventManager;

use EchoFusion\Contracts\EventManager\EventInterface;

class BaseEvent implements EventInterface
{
    protected string $name;

    protected array $params = [];

    protected bool $propagationStopped = false;

    public function __construct(string $name, array $params = [])
    {
        $this->name = $name;
        $this->params = $params;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function stopPropagation(bool $flag = true): void
    {
        $this->propagationStopped = $flag;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
