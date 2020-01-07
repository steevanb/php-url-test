<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseBodyTransformer;

class UuidResponseBodyTransformer implements ResponseBodyTransformerInterface
{
    public function transform(?string $response): ?string
    {
        if (is_string($response) === false) {
            return $response;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response;
        }

        $this->parseData($data);

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    protected function parseData(&$data): self
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $this->parseData($value);
            }
        } elseif (
            is_string($data)
            && strlen($data) === 36
            && is_int(preg_match('/^[0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-f]{12}$/', $data))
        ) {
            $data = '____UUID____';
        }

        return $this;
    }
}
