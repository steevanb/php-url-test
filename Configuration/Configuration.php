<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration
{
    public static function create(array $configuration, Configuration $defaultConfiguration = null): self
    {
        $return = new static();
        static::resolve($configuration, $defaultConfiguration);

        $return
            ->getRequest()
            ->setUrl($configuration['request']['url'])
            ->setTimeout($configuration['request']['timeout'])
            ->setPort($configuration['request']['port'])
            ->setMethod($configuration['request']['method'])
            ->setUserAgent($configuration['request']['userAgent'])
            ->setPostData($configuration['request']['postData'])
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

    protected static function resolve(array &$data, Configuration $defaultConfiguration = null)
    {
        if ($defaultConfiguration === null) {
            $defaultConfiguration = new Configuration();
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefault('request', [])
            ->setAllowedTypes('request', 'array')
            ->setDefault('expectedResponse', [])
            ->setAllowedTypes('expectedResponse', 'array')
            ->setDefault('response', [])
            ->setAllowedTypes('response', 'array');
        $data = $resolver->resolve($data);

        $requestResolver = new OptionsResolver();
        $requestResolver
            ->setDefault('url', $defaultConfiguration->getRequest()->getUrl())
            ->setAllowedTypes('url', 'string')
            ->setDefault('timeout', $defaultConfiguration->getRequest()->getTimeout())
            ->setAllowedTypes('timeout', 'int')
            ->setDefault('port', $defaultConfiguration->getRequest()->getPort())
            ->setAllowedTypes('port', 'int')
            ->setDefault('method', $defaultConfiguration->getRequest()->getMethod())
            ->setAllowedTypes('method', 'string')
            ->setDefault('headers', $defaultConfiguration->getRequest()->getHeaders())
            ->setAllowedTypes('headers', ['null', 'array'])
            ->setDefault('userAgent', $defaultConfiguration->getRequest()->getUserAgent())
            ->setAllowedTypes('userAgent', 'string')
            ->setDefault('postData', $defaultConfiguration->getRequest()->getPostData())
            ->setAllowedTypes('postData', ['null', 'string'])
            ->setDefault('referer', $defaultConfiguration->getRequest()->getReferer())
            ->setAllowedTypes('referer', ['null', 'string'])
            ->setDefault('allowRedirect', $defaultConfiguration->getRequest()->isAllowRedirect())
            ->setAllowedTypes('allowRedirect', 'bool');
        $data['request'] = $requestResolver->resolve($data['request']);

        $expectedResponseResolver = new OptionsResolver();
        $expectedResponseResolver
            ->setDefault('redirect', [])
            ->setAllowedTypes('redirect', 'array')
            ->setDefault('code', $defaultConfiguration->getResponse()->getCode())
            ->setAllowedTypes('code', ['null', 'int'])
            ->setDefault('numConnects', $defaultConfiguration->getResponse()->getNumConnects())
            ->setAllowedTypes('numConnects', ['null', 'int'])
            ->setDefault('size', $defaultConfiguration->getResponse()->getSize())
            ->setAllowedTypes('size', ['null', 'int'])
            ->setDefault('contentType', $defaultConfiguration->getResponse()->getContentType())
            ->setAllowedTypes('contentType', ['null', 'string'])
            ->setDefault('url', $defaultConfiguration->getResponse()->getUrl())
            ->setAllowedTypes('url', ['null', 'string'])
            ->setDefault('header', [])
            ->setAllowedTypes('header', 'array')
            ->setDefault('body', [])
            ->setAllowedTypes('body', 'array');
        $data['expectedResponse'] = $expectedResponseResolver->resolve($data['expectedResponse']);

        $expectedResponseRedirectResolver = new OptionsResolver();
        $expectedResponseRedirectResolver
            ->setDefault('min', $defaultConfiguration->getResponse()->getRedirectMin())
            ->setAllowedTypes('min', ['null', 'int'])
            ->setDefault('max', $defaultConfiguration->getResponse()->getRedirectMax())
            ->setAllowedTypes('max', ['null', 'int'])
            ->setDefault('count', $defaultConfiguration->getResponse()->getRedirectCount())
            ->setAllowedTypes('count', ['null', 'int']);
        $data['expectedResponse']['redirect'] = $expectedResponseRedirectResolver->resolve(
            $data['expectedResponse']['redirect']
        );

        $expectedResponseHeaderResolver = new OptionsResolver();
        $expectedResponseHeaderResolver
            ->setDefault('headers', [])
            ->setAllowedTypes('headers', 'array')
            ->setDefault('unallowedHeaders', $defaultConfiguration->getResponse()->getUnallowedHeaders())
            ->setAllowedTypes('unallowedHeaders', ['null', 'array'])
            ->setDefault('size', $defaultConfiguration->getResponse()->getHeaderSize())
            ->setAllowedTypes('size', ['null', 'int']);
        $data['expectedResponse']['header'] = $expectedResponseHeaderResolver->resolve(
            $data['expectedResponse']['header']
        );

        $expectedResponseBodyResolver = new OptionsResolver();
        $expectedResponseBodyResolver
            ->setDefault('content', $defaultConfiguration->getResponse()->getBody())
            ->setAllowedTypes('content', ['null', 'string'])
            ->setDefault('size', $defaultConfiguration->getResponse()->getBodySize())
            ->setAllowedTypes('size', ['null', 'int'])
            ->setDefault('transformer', $defaultConfiguration->getResponse()->getBodyTransformerName())
            ->setAllowedTypes('transformer', ['null', 'string'])
            ->setDefault('fileName', $defaultConfiguration->getResponse()->getBodyFileName())
            ->setAllowedTypes('fileName', ['null', 'string']);
        $data['expectedResponse']['body'] = $expectedResponseBodyResolver->resolve($data['expectedResponse']['body']);

        $responseResolver = new OptionsResolver();
        $responseResolver
            ->setDefault('body', [])
            ->setAllowedTypes('body', 'array');
        $data['response'] = $responseResolver->resolve($data['response']);

        $responseBodyResolver = new OptionsResolver();
        $responseBodyResolver
            ->setDefault('transformer', $defaultConfiguration->getResponse()->getRealResponseBodyTransformerName())
            ->setAllowedTypes('transformer', ['null', 'string'])
            ->setDefault('fileName', $defaultConfiguration->getResponse()->getRealResponseBodyFileName())
            ->setAllowedTypes('fileName', ['null', 'string']);
        $data['response']['body'] = $responseBodyResolver->resolve($data['response']['body']);
    }

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response($this);
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
