<?php

declare(strict_types=1);

namespace EchoFusion\EventManager;

interface EventInterface
{
    public function getName(): string;

    public function getParams(): array;

    public function stopPropagation(bool $flag): void;

    public function isPropagationStopped(): bool;
}
