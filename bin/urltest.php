#!/usr/bin/env php
<?php

declare(strict_types=1);

use steevanb\PhpUrlTest\Command\UrlTestCommand;
use Symfony\Component\Console\Application;

$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../../../autoload.php'
];
foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require($autoload);
        break;
    }
}

$application = new Application('urltest');
$command = new UrlTestCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
