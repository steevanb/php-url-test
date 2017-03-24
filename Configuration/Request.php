<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration;

class Request
{
    /** @var ?string */
    protected $url;

    /** @var int */
    protected $port = 80;

    /** @var string */
    protected $method = 'GET';

    /** @var string[] */
    protected $headers = [];

    /** @var string */
    protected $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36';

    /** @var string[] */
    protected $postFields = [];

    /** @var ?string */
    protected $referer;

    /** @var int */
    protected $timeout = 30;

    /** @var bool */
    protected $allowRedirect = false;

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setPostFields(array $postFields): self
    {
        $this->postFields = $postFields;

        return $this;
    }

    public function addPostField(string $name, string $value): self
    {
        $this->postFields[$name] = $value;

        return $this;
    }

    public function getPostFields(): array
    {
        return $this->postFields;
    }

    public function setReferer(?string $referer): self
    {
        $this->referer = $referer;

        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setAllowRedirect(bool $allowRedirect): self
    {
        $this->allowRedirect = $allowRedirect;

        return $this;
    }

    public function isAllowRedirect(): bool
    {
        return $this->allowRedirect;
    }
}
