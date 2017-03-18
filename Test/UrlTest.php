<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Test;

use Symfony\Component\Yaml\Yaml;

class UrlTest
{
    /** @var Request */
    protected $request;

    /** @var ExpectedResponse */
    protected $expectedResponse;

    /** @var ?Response */
    protected $response;

    /** @var int */
    protected $timeout = 30;

    /** @var bool */
    protected $allowRedirect = false;

    /** @var ?int */
    protected $redirectMin;

    /** @var ?int */
    protected $redirectMax;

    /** @var ?int */
    protected $redirectCount;

    /** @var ?bool */
    protected $isValid;

    public static function createFromYaml(string $yaml): UrlTest
    {
        if (is_readable($yaml) === false) {
            throw new \Exception('File "' . $yaml . '" does not exist or is not readable.');
        }

        $config = Yaml::parse(file_get_contents($yaml));
        $return = (new UrlTest())
            ->setTimeout($config['timeout'] ?? 30)
            ->setAllowRedirect($config['redirect']['allow'] ?? false)
            ->setRedirectMin($config['redirect']['min'] ?? null)
            ->setRedirectMax($config['redirect']['max'] ?? null)
            ->setRedirectCount($config['redirect']['count'] ?? null);

        $return
            ->getRequest()
            ->setUrl($config['request']['url'])
            ->setPort($config['request']['port'] ?? $return->getRequest()->getPort())
            ->setMethod($config['request']['method'] ?? $return->getRequest()->getMethod())
            ->setHeaders($config['request']['headers'] ?? [])
            ->setUserAgent($config['request']['userAgent'] ?? $return->getRequest()->getUserAgent())
            ->setPostFields($config['request']['postFields'] ?? [])
            ->setReferer($config['request']['referer'] ?? null);

        $return
            ->getExpectedResponse()
            ->setCode($config['expectedResponse']['code'] ?? null)
            ->setNumConnects($config['expectedResponse']['numConnects'] ?? null)
            ->setSize($config['expectedResponse']['size'] ?? null)
            ->setContentType($config['expectedResponse']['contentType'] ?? null)
            ->setUrl($config['expectedResponse']['url'] ?? null)
            ->setHeaders($config['expectedResponse']['headers'] ?? null)
            ->setHeaderSize($config['expectedResponse']['headerSize'] ?? null)
            ->setBody($config['expectedResponse']['body'] ?? null)
            ->setBodySize($config['expectedResponse']['bodySize'] ?? null);

        return $return;
    }


    public function __construct()
    {
        $this->request = new Request();
        $this->expectedResponse = new ExpectedResponse();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getExpectedResponse(): ExpectedResponse
    {
        return $this->expectedResponse;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
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

    public function setRedirectMin(?int $redirectMin): self
    {
        $this->redirectMin = $redirectMin;

        return $this;
    }

    public function getRedirectMin(): ?int
    {
        return $this->redirectMin;
    }

    public function setRedirectMax(?int $redirectMax): self
    {
        $this->redirectMax = $redirectMax;

        return $this;
    }

    public function getRedirectMax(): ?int
    {
        return $this->redirectMax;
    }

    public function setRedirectCount(?int $redirectCount): self
    {
        $this->redirectCount = $redirectCount;

        return $this;
    }

    public function getRedirectCount(): ?int
    {
        return $this->redirectCount;
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

    public function execute(): self
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getRequest()->getUrl(),
            CURLOPT_PORT => $this->getRequest()->getPort(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $this->getRequest()->getMethod(),
            CURLOPT_HEADER => count($this->getRequest()->getHeaders()) > 0,
            CURLOPT_HTTPHEADER => $this->getRequest()->getHeaders(),
            CURLOPT_REFERER => $this->getRequest()->getReferer(),
//            CURLOPT_POSTFIELDS => $this->getRequest()->getPostFields(),
            CURLOPT_FOLLOWLOCATION => $this->isAllowRedirect(),
            CURLOPT_TIMEOUT => $this->getTimeout(),
            CURLOPT_USERAGENT => $this->getRequest()->getUserAgent()
        ]);
        $response = null;
        try {
            $response = curl_exec($curl);
        } catch (\Exception $e) {
            $this->response = new Response(null, null, null, $e->getMessage());
        }

        if ($response === false) {
            $this->response = new Response(null, null, curl_errno($curl), curl_error($curl));
        } elseif ($response === null) {
            $this->response = new Response(null, null, null, 'Response should not be null.');
        } else {
            $this->response = new Response($curl, $response);
        }

        return $this;
    }

    public function isValid(): bool
    {
        if ($this->getResponse() instanceof Response === false) {
            throw new \Exception('You must call execute() before isValid().');
        }

        if ($this->isValid === null) {
            $this->isValid = true;
            $this->compare($this->getExpectedResponse()->getUrl(), $this->getResponse()->getUrl());
            $this->compare($this->getExpectedResponse()->getCode(), $this->getResponse()->getCode());
            $this->compare($this->getExpectedResponse()->getNumConnects(), $this->getResponse()->getNumConnects());
            $this->compare($this->getExpectedResponse()->getSize(), $this->getResponse()->getSize());
            $this->compare($this->getExpectedResponse()->getContentType(), $this->getResponse()->getContentType());
            $this->compare($this->getExpectedResponse()->getHeaderSize(), $this->getResponse()->getHeaderSize());
            $this->compare($this->getExpectedResponse()->getBody(), $this->getResponse()->getBody());
            $this->compare($this->getExpectedResponse()->getBodySize(), $this->getResponse()->getBodySize());
            if ($this->isAllowRedirect() === false && $this->getResponse()->getCode() === 302) {
                $this->isValid = false;
            } elseif ($this->isAllowRedirect()) {
                $this->compare($this->getRedirectCount(), $this->getResponse()->getRedirectCount());
                if (
                    (
                        $this->getRedirectMin() !== null
                        && $this->getRedirectMin() > $this->getResponse()->getRedirectCount()
                    ) || (
                        $this->getRedirectMax() !== null
                        && $this->getRedirectMax() < $this->getResponse()->getRedirectCount()
                    )
                ) {
                    $this->isValid = false;
                }
            }
        }

        return $this->isValid;
    }

    protected function compare($expected, $value): self
    {
        if ($expected !== null && $expected !== $value) {
            $this->isValid = false;
        }

        return $this;
    }
}
