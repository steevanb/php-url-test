<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration;

class Event
{
    public const EVENT_BEFORE_TEST = 'beforeTest';
    public const EVENT_AFTER_TEST = 'afterTest';

    /** @var ?string */
    protected $name;

    /** @var ?string */
    protected $command;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setCommand(?string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }
}
