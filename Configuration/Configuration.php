<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration;

use steevanb\PhpUrlTest\UrlTest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration
{
    public static function create(array $configuration, UrlTest $urlTest): self
    {
        $return = new static($urlTest);
        static::resolve($configuration, $return);

        $return
            ->getRequest()
            ->setUrl($configuration['request']['url'])
            ->setTimeout($configuration['request']['timeout'])
            ->setPort($configuration['request']['port'])
            ->setMethod($configuration['request']['method'])
            ->setUserAgent($configuration['request']['userAgent'])
            ->setPostFields($configuration['request']['postFields'])
            ->setReferer($configuration['request']['referer'])
            ->setAllowRedirect($configuration['request']['allowRedirect'])
            ->setHeaders($configuration['request']['headers']);

        $return
            ->getResponse()
            ->setUrl($configuration['expectedResponse']['url'])
            ->setCode($configuration['expectedResponse']['code'])
            ->setSize($configuration['expectedResponse']['size'])
            ->setContentType($configuration['expectedResponse']['contentType'])
            ->setNumConnects($configuration['expectedResponse']['numConnects'])
            ->setRedirectMin($configuration['expectedResponse']['redirect']['min'])
            ->setRedirectMax($configuration['expectedResponse']['redirect']['max'])
            ->setRedirectCount($configuration['expectedResponse']['redirect']['count'])
            ->setHeaders($configuration['expectedResponse']['header']['headers'])
            ->setUnallowedHeaders($configuration['expectedResponse']['header']['unallowedHeaders'])
            ->setHeaderSize($configuration['expectedResponse']['header']['size'])
            ->setBody($configuration['expectedResponse']['body']['content'])
            ->setBodySize($configuration['expectedResponse']['body']['size'])
            ->setBodyTransformerName($configuration['expectedResponse']['body']['transformer'])
            ->setBodyFileName($configuration['expectedResponse']['body']['fileName']);

        $return
            ->getResponse()
            ->setRealResponseBodyTransformerName($configuration['response']['body']['transformer'])
            ->setRealResponseBodyFileName($configuration['response']['body']['fileName']);

        return $return;
    }

    public static function resolve(array &$data, Configuration $configuration)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('request')
            ->setAllowedTypes('request', 'array')
            ->setDefault('expectedResponse', [])
            ->setAllowedTypes('expectedResponse', 'array')
            ->setDefault('response', [])
            ->setAllowedTypes('response', 'array');
        $data = $resolver->resolve($data);

        $requestResolver = new OptionsResolver();
        $requestResolver
            ->setDefined('url')
            ->setAllowedTypes('url', 'string')
            ->setDefault('timeout', $configuration->getRequest()->getTimeout())
            ->setAllowedTypes('timeout', 'int')
            ->setDefault('port', $configuration->getRequest()->getPort())
            ->setAllowedTypes('port', 'int')
            ->setDefault('method', $configuration->getRequest()->getMethod())
            ->setAllowedTypes('method', 'string')
            ->setDefault('headers', [])
            ->setAllowedTypes('headers', 'array')
            ->setDefault('userAgent', $configuration->getRequest()->getUserAgent())
            ->setAllowedTypes('userAgent', 'string')
            ->setDefault('postFields', [])
            ->setAllowedTypes('postFields', 'array')
            ->setDefault('referer', null)
            ->setAllowedTypes('referer', ['null', 'string'])
            ->setDefault('allowRedirect', $configuration->getRequest()->isAllowRedirect())
            ->setAllowedTypes('allowRedirect', 'bool');
        $data['request'] = $requestResolver->resolve($data['request']);

        $expectedResponseResolver = new OptionsResolver();
        $expectedResponseResolver
            ->setDefault('redirect', [])
            ->setAllowedTypes('redirect', 'array')
            ->setDefault('code', null)
            ->setAllowedTypes('code', ['null', 'int'])
            ->setDefault('numConnects', null)
            ->setAllowedTypes('numConnects', ['null', 'int'])
            ->setDefault('size', null)
            ->setAllowedTypes('size', ['null', 'int'])
            ->setDefault('contentType', null)
            ->setAllowedTypes('contentType', ['null', 'string'])
            ->setDefault('url', null)
            ->setAllowedTypes('url', ['null', 'string'])
            ->setDefault('header', [])
            ->setAllowedTypes('header', 'array')
            ->setDefault('body', [])
            ->setAllowedTypes('body', 'array');
        $data['expectedResponse'] = $expectedResponseResolver->resolve($data['expectedResponse']);

        $expectedResponseRedirectResolver = new OptionsResolver();
        $expectedResponseRedirectResolver
            ->setDefault('min', null)
            ->setAllowedTypes('min', ['null', 'int'])
            ->setDefault('max', null)
            ->setAllowedTypes('max', ['null', 'int'])
            ->setDefault('count', null)
            ->setAllowedTypes('count', ['null', 'int']);
        $data['expectedResponse']['redirect'] = $expectedResponseRedirectResolver->resolve(
            $data['expectedResponse']['redirect']
        );

        $expectedResponseHeaderResolver = new OptionsResolver();
        $expectedResponseHeaderResolver
            ->setDefault('headers', [])
            ->setAllowedTypes('headers', 'array')
            ->setDefault('unallowedHeaders', [])
            ->setAllowedTypes('unallowedHeaders', 'array')
            ->setDefault('size', null)
            ->setAllowedTypes('size', ['null', 'int']);
        $data['expectedResponse']['header'] = $expectedResponseHeaderResolver->resolve(
            $data['expectedResponse']['header']
        );

        $expectedResponseBodyResolver = new OptionsResolver();
        $expectedResponseBodyResolver
            ->setDefault('content', null)
            ->setAllowedTypes('content', ['null', 'string'])
            ->setDefault('size', null)
            ->setAllowedTypes('size', ['null', 'int'])
            ->setDefault('transformer', null)
            ->setAllowedTypes('transformer', ['null', 'string'])
            ->setDefault('fileName', null)
            ->setAllowedTypes('fileName', ['null', 'string']);
        $data['expectedResponse']['body'] = $expectedResponseBodyResolver->resolve($data['expectedResponse']['body']);

        $responseResolver = new OptionsResolver();
        $responseResolver
            ->setDefault('body', [])
            ->setAllowedTypes('body', 'array');
        $data['response'] = $responseResolver->resolve($data['response']);

        $responseBodyResolver = new OptionsResolver();
        $responseBodyResolver
            ->setDefault('transformer', null)
            ->setAllowedTypes('transformer', ['null', 'string'])
            ->setDefault('fileName', null)
            ->setAllowedTypes('fileName', ['null', 'string']);
        $data['response']['body'] = $responseBodyResolver->resolve($data['response']['body']);
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
