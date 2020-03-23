<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Response;

use Symfony\Component\Process\Process;

class Event
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $command;

    /** @var Process */
    protected $process;

    public function __construct(string $name, string $command, Process $process)
    {
        $this->name = $name;
        $this->command = $command;
        $this->process = $process;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
