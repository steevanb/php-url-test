#!/usr/bin/env php
<?php

declare(strict_types=1);

use steevanb\PhpUrlTest\Command\UrlTestCommand;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$application = new Application('urltest');
$command = new UrlTestCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
