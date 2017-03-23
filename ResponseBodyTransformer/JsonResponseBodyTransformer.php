<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseBodyTransformer;

class JsonResponseBodyTransformer implements ResponseBodyTransformerInterface
{
    public function transform(?string $response): ?string
    {
        return $response === null ? null : trim(json_encode(json_decode($response)));
    }
}
