<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseBodyTransformer;

interface ResponseBodyTransformerInterface
{
    public function transform(?string $response): ?string;
}
