<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration\Dumper;

use steevanb\PhpUrlTest\UrlTest;

interface ConfigurationDumperInterface
{
    public function dump(UrlTest $urlTest): void;
}
