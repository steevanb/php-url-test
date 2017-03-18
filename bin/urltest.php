#!/usr/bin/env php
<?php

declare(strict_types=1);

use steevanb\PhpUrlTest\Test\UrlTest;

require dirname(__FILE__) . '/../vendor/autoload.php';

$urlTest = UrlTest::createFromYaml($argv[1]);
$urlTest->execute();

$comparator = new \steevanb\PhpUrlTest\ResponseComparator\ConsoleResponseComparator();
$comparator->compare($urlTest, \steevanb\PhpUrlTest\ResponseComparator\ResponseComparatorInterface::VERBOSE_HIGH);
