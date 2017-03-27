<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseComparator;

use steevanb\PhpUrlTest\UrlTest;

interface ResponseComparatorInterface
{
    public const VERBOSITY_NORMAL = 0;
    public const VERBOSITY_VERBOSE = 1;
    public const VERBOSITY_VERY_VERBOSE = 2;
    public const VERBOSITY_DEBUG = 3;

    public function compare(UrlTest $urlTest, int $verbosity = self::VERBOSITY_NORMAL): ResponseComparatorInterface;
}
