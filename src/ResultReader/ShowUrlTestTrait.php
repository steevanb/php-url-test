<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResultReader;

use steevanb\PhpUrlTest\UrlTest;

trait ShowUrlTestTrait
{
    protected function showUrlTest(UrlTest $urlTest, bool $showSuccess, bool $showError): bool
    {
        return
            $urlTest->isExecuted()
            && (
                ($urlTest->isValid() === true && $showSuccess === true)
                || ($urlTest->isValid() === false && $showError === true)
            );
    }
}
