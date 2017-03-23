<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Test;

use steevanb\PhpUrlTest\ResponseBodyTransformer\{
    ResponseBodyTransformerInterface,
    JsonResponseBodyTransformer
};
use steevanb\PhpYaml\Parser;

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

    /** @var ?string */
    protected $responseBodyTransformerName;

    /** @var ?string */
    protected $responseBodyFileName;

    /** @var ?bool */
    protected $isValid;

    /** @var ResponseBodyTransformerInterface[] */
    protected $responseBodyTransformers = [];

    public static function createFromYaml(string $yaml): UrlTest
    {
        if (is_readable($yaml) === false) {
            throw new \Exception('File "' . $yaml . '" does not exist or is not readable.');
        }

        Parser::registerFileFunction();
        $config = (new Parser())->parse(file_get_contents($yaml));
        $return = (new UrlTest())
            ->setTimeout($config['timeout'] ?? 30)
            ->setResponseBodyTransformerName($config['response']['body']['transformer'] ?? null)
            ->setResponseBodyFileName($config['response']['body']['fileName'] ?? null)
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
            ->setHeaders($config['expectedResponse']['header']['headers'] ?? null)
            ->setHeaderSize($config['expectedResponse']['header']['size'] ?? null)
            ->setBody($config['expectedResponse']['body']['content'] ?? null)
            ->setBodySize($config['expectedResponse']['body']['size'] ?? null)
            ->setBodyTransformerName($config['expectedResponse']['body']['transformer'] ?? null)
            ->setBodyFileName($config['expectedResponse']['body']['fileName'] ?? null);

        return $return;
    }


    public function __construct()
    {
        $this->request = new Request();
        $this->expectedResponse = new ExpectedResponse($this);
        $this->addResponseBodyTransformer('json', new JsonResponseBodyTransformer());
    }

    public function addResponseBodyTransformer(string $name, ResponseBodyTransformerInterface $transformer): self
    {
        $this->responseBodyTransformers[$name] = $transformer;

        return $this;
    }

    /** @return ResponseBodyTransformerInterface[] */
    public function getResponseBodyTransformers(): array
    {
        return $this->responseBodyTransformers;
    }

    public function getResponseBodyTransformer(string $name): ResponseBodyTransformerInterface
    {
        if (isset($this->getResponseBodyTransformers()[$name]) === false) {
            throw new \Exception("Response body transformer \"$name\" not found.");
        }

        return $this->getResponseBodyTransformers()[$name];
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

    public function setResponseBodyTransformerName(?string $transformer): self
    {
        $this->responseBodyTransformerName = $transformer;

        return $this;
    }

    public function getResponseBodyTransformerName(): ?string
    {
        return $this->responseBodyTransformerName;
    }

    public function setResponseBodyFileName(?string $responseBodyFileName): self
    {
        $this->responseBodyFileName = $responseBodyFileName;

        return $this;
    }

    public function getResponseBodyFileName(): ?string
    {
        return $this->responseBodyFileName;
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
        $this->defineCurlOptions($curl);

        $response = null;
        $timeBeforeCall = null;
        $timeAfterCall = null;
        try {
            $timeBeforeCall = microtime(true);
            $response = curl_exec($curl);
            $timeAfterCall = microtime(true);
        } catch (\Exception $e) {
            $this->response = new Response($this, null, null, null, null, $e->getMessage());
        }

        if ($response === null) {
            $this->response = new Response(
                $this,
                null,
                null,
                null,
                null,
                'Response should not be null.'
            );
        } elseif ($response === false) {
            $this->response = new Response(
                $this,
                null,
                null,
                intval(($timeAfterCall - $timeBeforeCall) * 1000),
                curl_errno($curl),
                curl_error($curl)
            );
        } else {
            $this->response = new Response($this, $curl, $response, intval(($timeAfterCall - $timeBeforeCall) * 1000));
            if ($this->getResponseBodyFileName() !== null) {
                file_put_contents(
                    $this->getResponseBodyFileName(),
                    $this->getTransformedBody(
                        $this->getResponse()->getBody(),
                        $this->getResponseBodyTransformerName()
                    )
                );
            }
            if ($this->getExpectedResponse()->getBodyFileName() !== null) {
                file_put_contents(
                    $this->getExpectedResponse()->getBodyFileName(),
                    $this->getTransformedBody(
                        $this->getExpectedResponse()->getBody(),
                        $this->getExpectedResponse()->getBodyTransformerName()
                    )
                );
            }
        }

        return $this;
    }

    public function getTransformedBody(?string $content, ?string $transformer): ?string
    {
        return $transformer !== null ? $this->getResponseBodyTransformer($transformer)->transform($content) : $content;
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

            $expectedBody = $this->getTransformedBody(
                $this->getExpectedResponse()->getBody(),
                $this->getExpectedResponse()->getBodyTransformerName()
            );
            $responseBody = $this->getTransformedBody(
                $this->getResponse()->getBody(),
                $this->getResponseBodyTransformerName()
            );
            $this->compare($expectedBody, $responseBody);
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

    protected function defineCurlOptions($curl): self
    {
        $headers = [];
        foreach ($this->getRequest()->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getRequest()->getUrl(),
            CURLOPT_PORT => $this->getRequest()->getPort(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $this->getRequest()->getMethod(),
            CURLOPT_HEADER => count($this->getRequest()->getHeaders()) > 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_REFERER => $this->getRequest()->getReferer(),
//            CURLOPT_POSTFIELDS => $this->getRequest()->getPostFields(),
            CURLOPT_FOLLOWLOCATION => $this->isAllowRedirect(),
            CURLOPT_TIMEOUT => $this->getTimeout(),
            CURLOPT_USERAGENT => $this->getRequest()->getUserAgent()
        ]);

        return $this;
    }

    protected function compare($expected, $value): self
    {
        if ($expected !== null && $expected !== $value) {
            $this->isValid = false;
        }

        return $this;
    }
}
