<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest;

use steevanb\PhpUrlTest\{
    Configuration\Configuration,
    ResponseBodyTransformer\ResponseBodyTransformerInterface,
    ResponseBodyTransformer\JsonResponseBodyTransformer
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
    protected $isValid;

    /** @var ResponseBodyTransformerInterface[] */
    protected $responseBodyTransformers = [];

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->configuration = new Configuration($this);
        $this->addResponseBodyTransformer('json', new JsonResponseBodyTransformer());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setConfiguration(Configuration $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
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

    public function isValid(): bool
    {
        if ($this->getResponse() instanceof Response === false) {
            throw new \Exception('You must call execute() before isValid().');
        }

        if ($this->isValid === null) {
            $request = $this->getConfiguration()->getRequest();
            $expectedResponse = $this->getConfiguration()->getResponse();
            $this->isValid = true;
            $this->compare($expectedResponse->getUrl(), $this->getResponse()->getUrl());
            $this->compare($expectedResponse->getCode(), $this->getResponse()->getCode());
            $this->compare($expectedResponse->getNumConnects(), $this->getResponse()->getNumConnects());
            $this->compare($expectedResponse->getSize(), $this->getResponse()->getSize());
            $this->compare($expectedResponse->getContentType(), $this->getResponse()->getContentType());
            $this->compare($expectedResponse->getHeaderSize(), $this->getResponse()->getHeaderSize());

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
                $this->isValid = false;
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
                    $this->isValid = false;
                }
            }
        }

        return $this->isValid;
    }

    protected function defineCurlOptions($curl): self
    {
        $request = $this->getConfiguration()->getRequest();

        $headers = [];
        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt_array($curl, [
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_PORT => $request->getPort(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HEADER => count($request->getHeaders()) > 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_REFERER => $request->getReferer(),
//            CURLOPT_POSTFIELDS => $this->getRequest()->getPostFields(),
            CURLOPT_FOLLOWLOCATION => $request->isAllowRedirect(),
            CURLOPT_TIMEOUT => $request->getTimeout(),
            CURLOPT_USERAGENT => $request->getUserAgent()
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
