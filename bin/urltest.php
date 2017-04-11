#!/usr/bin/env php
<?php

declare(strict_types=1);

use steevanb\PhpUrlTest\Command\UrlTestCommand;
use Symfony\Component\Console\Application;

requireAutoload();

$application = new Application('urltest');
$command = new UrlTestCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();

function requireAutoload(): void
{
    foreach ($_SERVER['argv'] as $index => $arg) {
        if (substr($arg, 0, 11) === '--autoload=') {
            $autoloads = [substr($arg, 11)];
            break;
        }
    }
    $autoloads = $autoloads ?? [
            __DIR__ . '/vendor/autoload.php',
            __DIR__ . '/../../autoload.php',
            __DIR__ . '/../../../autoload.php'
        ];
    $autloadIncluded = false;
    foreach ($autoloads as $autoload) {
        if (file_exists($autoload)) {
            require($autoload);
            $autloadIncluded = true;
            break;
        }
    }
    if ($autloadIncluded === false) {
        throw new \Exception('Autoload file not found ' . implode(', ', $autoloads));
    }
}
