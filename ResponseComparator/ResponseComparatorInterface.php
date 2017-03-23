<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseComparator;

use steevanb\PhpUrlTest\Test\UrlTest;

interface ResponseComparatorInterface
{
    public const VERBOSE_LIGHT = 1;
    public const VERBOSE_MEDIUM = 2;
    public const VERBOSE_HIGH = 3;
    public function compare(UrlTest $urlTest, int $verbose): ResponseComparatorInterface;
}
