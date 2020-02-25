<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest;

use steevanb\PhpUrlTest\{
    Configuration\Configuration,
    ResponseBodyTransformer\ResponseBodyTransformerInterface,
    ResponseBodyTransformer\JsonResponseBodyTransformer,
    ResponseBodyTransformer\UuidResponseBodyTransformer
};

class UrlTest
{
    /** @var string */
    protected $id;

    /** @var Configuration */
    protected $configuration;

    /** @var ?Response */
    protected $response;

    /** @var ?bool */
    protected $valid;

    /** @var ResponseBodyTransformerInterface[] */
    protected $responseBodyTransformers = [];

    /** @var ?string */
    protected $parallelResponse;

    public function __construct(string $id, Configuration $configuration)
    {
        $this->id = $id;
        $this->configuration = $configuration;

        $this
            ->addResponseBodyTransformer('json', new JsonResponseBodyTransformer())
            ->addResponseBodyTransformer('uuid', new UuidResponseBodyTransformer());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function addResponseBodyTransformer(string $name, ResponseBodyTransformerInterface $transformer): self
    {
        $this->responseBodyTransformers[$name] = $transformer;

        return $this;
    }

    public function hasResponseBodyTransformer(string $name): bool
    {
        return array_key_exists($name, $this->responseBodyTransformers);
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

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setParallelResponse(?string $response): self
    {
        $this->parallelResponse = $response;

        return $this;
    }

    public function getParallelResponse(): ?string
    {
        return $this->parallelResponse;
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
            if ($this->getConfiguration()->getResponse()->getRealResponseBodyFileName() !== null) {
                file_put_contents(
                    $this->getConfiguration()->getResponse()->getRealResponseBodyFileName(),
                    $this->getTransformedBody(
                        $this->getResponse()->getBody(),
                        $this->getConfiguration()->getResponse()->getRealResponseBodyTransformerName()
                    )
                );
            }
            if ($this->getConfiguration()->getResponse()->getBodyFileName() !== null) {
                file_put_contents(
                    $this->getConfiguration()->getResponse()->getBodyFileName(),
                    $this->getTransformedBody(
                        $this->getConfiguration()->getResponse()->getBody(),
                        $this->getConfiguration()->getResponse()->getBodyTransformerName()
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

    public function isExecuted(): bool
    {
        return $this->getResponse() instanceof Response;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function isValid(): bool
    {
        if ($this->valid === null) {
            if ($this->isExecuted() === false) {
                throw new \Exception('Test is not executed.');
            }

            $request = $this->getConfiguration()->getRequest();
            $expectedResponse = $this->getConfiguration()->getResponse();
            $this->valid = true;
            $this->compare($expectedResponse->getUrl(), $this->getResponse()->getUrl());
            $this->compare($expectedResponse->getCode(), $this->getResponse()->getCode());
            $this->compare($expectedResponse->getNumConnects(), $this->getResponse()->getNumConnects());
            $this->compare($expectedResponse->getSize(), $this->getResponse()->getSize());
            $this->compare($expectedResponse->getContentType(), $this->getResponse()->getContentType());
            $this->compare($expectedResponse->getHeaderSize(), $this->getResponse()->getHeaderSize());

            foreach ($this->getResponse()->getHeaders() ?? [] as $headerName => $headerValue) {
                foreach ($expectedResponse->getHeaders() ?? [] as $allowedHeaderName => $allowedHeaderValue) {
                    if ($allowedHeaderName === $headerName && (string) $allowedHeaderValue !== $headerValue) {
                        $this->valid = false;
                        break;
                    }
                }

                if (in_array($headerName, $expectedResponse->getUnallowedHeaders() ?? [])) {
                    $this->valid = false;
                }
            }
            $responseHeaderNames = array_keys($this->getResponse()->getHeaders());
            $responseHeaderValues = $this->getResponse()->getHeaders();
            foreach ($expectedResponse->getHeaders() ?? [] as $headerName => $headerValue) {
                if (
                    in_array($headerName, $responseHeaderNames) === false
                    || (string) $headerValue !== $responseHeaderValues[$headerName]
                ) {
                    $this->valid = false;
                }
            }

            $expectedBody = $this->getTransformedBody(
                $expectedResponse->getBody(),
                $expectedResponse->getBodyTransformerName()
            );
            $responseBody = $this->getTransformedBody(
                $this->getResponse()->getBody(),
                $expectedResponse->getRealResponseBodyTransformerName()
            );
            $this->compare($expectedBody, $responseBody);
            $this->compare($expectedResponse->getBodySize(), $this->getResponse()->getBodySize());
            if ($request->isAllowRedirect() === false && $this->getResponse()->getCode() === 302) {
                $this->valid = false;
            } elseif ($request->isAllowRedirect()) {
                $this->compare($expectedResponse->getRedirectCount(), $this->getResponse()->getRedirectCount());
                if (
                    (
                        $expectedResponse->getRedirectMin() !== null
                        && $expectedResponse->getRedirectMin() > $this->getResponse()->getRedirectCount()
                    ) || (
                        $expectedResponse->getRedirectMax() !== null
                        && $expectedResponse->getRedirectMax() < $this->getResponse()->getRedirectCount()
                    )
                ) {
                    $this->valid = false;
                }
            }
        }

        return $this->valid;
    }

    protected function defineCurlOptions($curl): self
    {
        $request = $this->getConfiguration()->getRequest();

        $headers = [];
        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $request->getUrl(),
                CURLOPT_PORT => $request->getPort(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $request->getMethod(),
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_REFERER => $request->getReferer(),
                CURLOPT_POSTFIELDS => $request->getPostData(),
                CURLOPT_FOLLOWLOCATION => $request->isAllowRedirect(),
                CURLOPT_TIMEOUT => $request->getTimeout(),
                CURLOPT_USERAGENT => $request->getUserAgent()
            ]
        );

        return $this;
    }

    protected function compare($expected, $value): self
    {
        if ($expected !== null && $expected !== $value) {
            $this->valid = false;
        }

        return $this;
    }
}
