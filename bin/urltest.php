#!/usr/bin/env php
<?php

use steevanb\PhpUrlTest\Test\UrlTest;

require dirname(__FILE__) . '/../vendor/autoload.php';


$test = UrlTest::createFromYaml($argv[1]);
$test->execute();

//var_dump($test->getResponse()->getCode(), $test->getResponse()->getTime());
