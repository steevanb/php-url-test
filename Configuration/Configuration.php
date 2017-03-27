<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration;

use steevanb\PhpUrlTest\UrlTest;

class Configuration
{
    public static function create(array $config, UrlTest $urlTest): self
    {
        $return = new static($urlTest);

        $return
            ->getRequest()
            ->setTimeout($config['request']['timeout'] ?? 30)
            ->setUrl($config['request']['url'])
            ->setPort($config['request']['port'] ?? $return->getRequest()->getPort())
            ->setMethod($config['request']['method'] ?? $return->getRequest()->getMethod())
            ->setHeaders($config['request']['headers'] ?? [])
            ->setUserAgent($config['request']['userAgent'] ?? $return->getRequest()->getUserAgent())
            ->setPostFields($config['request']['postFields'] ?? [])
            ->setReferer($config['request']['referer'] ?? null)
            ->setAllowRedirect($config['request']['allowRedirect'] ?? false);

        $return
            ->getResponse()
            ->setRedirectMin($config['expectedResponse']['redirect']['min'] ?? null)
            ->setRedirectMax($config['expectedResponse']['redirect']['max'] ?? null)
            ->setRedirectCount($config['expectedResponse']['redirect']['count'] ?? null)
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

        $return
            ->getResponse()
            ->setRealResponseBodyTransformerName($config['response']['body']['transformer'] ?? null)
            ->setRealResponseBodyFileName($config['response']['body']['fileName'] ?? null);

        return $return;
    }

    /** @var UrlTest */
    protected $urlTest;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    public function __construct(UrlTest $urlTest)
    {
        $this->urlTest = $urlTest;
        $this->request = new Request();
        $this->response = new Response($this);
        $urlTest->setConfiguration($this);
    }

    public function getUrlTest(): UrlTest
    {
        return $this->urlTest;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
