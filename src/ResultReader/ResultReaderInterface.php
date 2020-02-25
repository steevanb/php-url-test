<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResultReader;

interface ResultReaderInterface
{
    public function read(array $urlTests, bool $showSuccess, bool $showError, int $verbosity): void;
}
