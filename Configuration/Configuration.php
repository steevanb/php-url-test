<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration;

use steevanb\PhpUrlTest\UrlTest;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration
{
    public static function create(
        string $id,
        array $configuration,
        array $parentConfiguration = [],
        array $defaultConfiguration = []
    ): self
    {
        $return = new static();
        $return->setId($id);
        static::resolve($configuration, $parentConfiguration, $defaultConfiguration);

        $return->setPosition($configuration['position']);

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

    protected static function resolve(array &$data, array $parent, array $default = []): void
    {
        $defaultConfiguration = (new UrlTest('defaultConfiguration', new Configuration()))->getConfiguration();

        $resolver = new OptionsResolver();
        $resolver
            ->setDefault('parent', null)
            ->setAllowedTypes('parent', ['null', 'string'])
            ->setDefault('position', null)
            ->setAllowedTypes('position', ['null', 'int'])
            ->setDefault('request', [])
            ->setAllowedTypes('request', 'array')
            ->setDefault('expectedResponse', [])
            ->setAllowedTypes('expectedResponse', 'array')
            ->setDefault('response', [])
            ->setAllowedTypes('response', 'array');
        $data = $resolver->resolve($data);

        $requestResolver = new OptionsResolver();
        $requestResolver
            ->setDefault(
                'url',
                $parent['request']['url']
                    ?? $default['request']['url']
                    ?? $defaultConfiguration->getRequest()->getUrl()
            )
            ->setAllowedTypes('url', ['null', 'string'])
            ->setDefault(
                'timeout',
                $parent['request']['timeout']
                    ?? $default['request']['timeout']
                    ?? $defaultConfiguration->getRequest()->getTimeout()
            )
            ->setAllowedTypes('timeout', 'int')
            ->setDefault(
                'port',
                $parent['request']['port']
                    ?? $default['request']['port']
                    ?? $defaultConfiguration->getRequest()->getPort()
            )
            ->setAllowedTypes('port', 'int')
            ->setDefault(
                'method',
                $parent['request']['method']
                    ?? $default['request']['method']
                    ?? $defaultConfiguration->getRequest()->getMethod()
            )
            ->setAllowedTypes('method', 'string')
            ->setDefault(
                'headers',
                $parent['request']['headers']
                    ?? $default['request']['headers']
                    ?? $defaultConfiguration->getRequest()->getHeaders()
            )
            ->setAllowedTypes('headers', ['null', 'array'])
            ->setDefault(
                'userAgent',
                $parent['request']['userAgent']
                    ?? $default['request']['userAgent']
                    ?? $defaultConfiguration->getRequest()->getUserAgent()
            )
            ->setAllowedTypes('userAgent', 'string')
            ->setDefault(
                'postData',
                $parent['request']['postData']
                    ?? $default['request']['postData']
                    ?? $defaultConfiguration->getRequest()->getPostData()
            )
            ->setAllowedTypes('postData', ['null', 'string'])
            ->setDefault(
                'referer',
                $parent['request']['referer']
                    ?? $default['request']['referer']
                    ?? $defaultConfiguration->getRequest()->getReferer()
            )
            ->setAllowedTypes('referer', ['null', 'string'])
            ->setDefault(
                'allowRedirect',
                $parent['request']['allowRedirect']
                    ?? $default['request']['allowRedirect']
                    ?? $defaultConfiguration->getRequest()->isAllowRedirect()
            )
            ->setAllowedTypes('allowRedirect', 'bool');
        $data['request'] = $requestResolver->resolve($data['request']);

        $expectedResponseResolver = new OptionsResolver();
        $expectedResponseResolver
            ->setDefault('redirect', [])
            ->setAllowedTypes('redirect', 'array')
            ->setDefault(
                'code',
                $parent['expectedResponse']['code']
                    ?? $default['expectedResponse']['code']
                    ?? $defaultConfiguration->getResponse()->getCode()
            )
            ->setAllowedTypes('code', ['null', 'int'])
            ->setDefault(
                'numConnects',
                $parent['expectedResponse']['numConnects']
                    ?? $default['expectedResponse']['numConnects']
                    ?? $defaultConfiguration->getResponse()->getNumConnects()
            )
            ->setAllowedTypes('numConnects', ['null', 'int'])
            ->setDefault(
                'size',
                $parent['expectedResponse']['size']
                    ?? $default['expectedResponse']['size']
                    ?? $defaultConfiguration->getResponse()->getSize()
            )
            ->setAllowedTypes('size', ['null', 'int'])
            ->setDefault(
                'contentType',
                $parent['expectedResponse']['contentType']
                    ?? $default['expectedResponse']['contentType']
                    ?? $defaultConfiguration->getResponse()->getContentType()
            )
            ->setAllowedTypes('contentType', ['null', 'string'])
            ->setDefault(
                'url',
                $parent['expectedResponse']['url']
                    ?? $default['expectedResponse']['url']
                    ?? $defaultConfiguration->getResponse()->getUrl()
            )
            ->setAllowedTypes('url', ['null', 'string'])
            ->setDefault('header', [])
            ->setAllowedTypes('header', 'array')
            ->setDefault('body', [])
            ->setAllowedTypes('body', 'array');
        $data['expectedResponse'] = $expectedResponseResolver->resolve($data['expectedResponse']);

        $expectedResponseRedirectResolver = new OptionsResolver();
        $expectedResponseRedirectResolver
            ->setDefault(
                'min',
                $parent['expectedResponse']['redirect']['min']
                    ?? $default['expectedResponse']['redirect']['min']
                    ?? $defaultConfiguration->getResponse()->getRedirectMin()
            )
            ->setAllowedTypes('min', ['null', 'int'])
            ->setDefault(
                'max',
                $parent['expectedResponse']['redirect']['max']
                    ?? $default['expectedResponse']['redirect']['max']
                    ?? $defaultConfiguration->getResponse()->getRedirectMax()
            )
            ->setAllowedTypes('max', ['null', 'int'])
            ->setDefault(
                'count',
                $parent['expectedResponse']['redirect']['count']
                    ?? $default['expectedResponse']['redirect']['count']
                    ?? $defaultConfiguration->getResponse()->getRedirectCount()
            )
            ->setAllowedTypes('count', ['null', 'int']);
        $data['expectedResponse']['redirect'] = $expectedResponseRedirectResolver->resolve(
            $data['expectedResponse']['redirect']
        );

        $expectedResponseHeaderResolver = new OptionsResolver();
        $expectedResponseHeaderResolver
            ->setDefault('headers', [])
            ->setAllowedTypes('headers', 'array')
            ->setDefault(
                'unallowedHeaders',
                $parent['expectedResponse']['header']['unallowedHeaders']
                    ?? $default['expectedResponse']['header']['unallowedHeaders']
                    ?? $defaultConfiguration->getResponse()->getUnallowedHeaders()
            )
            ->setAllowedTypes('unallowedHeaders', ['null', 'array'])
            ->setDefault(
                'size',
                $parent['expectedResponse']['header']['size']
                    ?? $default['expectedResponse']['header']['size']
                    ?? $defaultConfiguration->getResponse()->getHeaderSize()
            )
            ->setAllowedTypes('size', ['null', 'int']);
        $data['expectedResponse']['header'] = $expectedResponseHeaderResolver->resolve(
            $data['expectedResponse']['header']
        );

        $expectedResponseBodyResolver = new OptionsResolver();
        $expectedResponseBodyResolver
            ->setDefault(
                'content',
                $parent['expectedResponse']['body']['content']
                    ?? $default['expectedResponse']['body']['content']
                    ?? $defaultConfiguration->getResponse()->getBody()
            )
            ->setAllowedTypes('content', ['null', 'string'])
            ->setDefault(
                'size',
                $parent['expectedResponse']['body']['size']
                    ?? $default['expectedResponse']['body']['size']
                    ?? $defaultConfiguration->getResponse()->getBodySize()
            )
            ->setAllowedTypes('size', ['null', 'int'])
            ->setDefault(
                'transformer',
                $parent['expectedResponse']['body']['transformer']
                    ?? $default['expectedResponse']['body']['transformer']
                    ?? $defaultConfiguration->getResponse()->getBodyTransformerName()
            )
            ->setAllowedTypes('transformer', ['null', 'string'])
            ->setDefault(
                'fileName',
                $parent['expectedResponse']['body']['fileName']
                    ?? $default['expectedResponse']['body']['fileName']
                    ?? $defaultConfiguration->getResponse()->getBodyFileName()
            )
            ->setAllowedTypes('fileName', ['null', 'string']);
        $data['expectedResponse']['body'] = $expectedResponseBodyResolver->resolve($data['expectedResponse']['body']);

        $responseResolver = new OptionsResolver();
        $responseResolver
            ->setDefault('body', [])
            ->setAllowedTypes('body', 'array');
        $data['response'] = $responseResolver->resolve($data['response']);

        $responseBodyResolver = new OptionsResolver();
        $responseBodyResolver
            ->setDefault(
                'transformer',
                $parent['response']['body']['transformer']
                    ?? $default['response']['body']['transformer']
                    ?? $defaultConfiguration->getResponse()->getRealResponseBodyTransformerName()
            )
            ->setAllowedTypes('transformer', ['null', 'string'])
            ->setDefault(
                'fileName',
                $parent['response']['body']['fileName']
                    ?? $default['response']['body']['fileName']
                    ?? $defaultConfiguration->getResponse()->getRealResponseBodyFileName()
            )
            ->setAllowedTypes('fileName', ['null', 'string']);
        $data['response']['body'] = $responseBodyResolver->resolve($data['response']['body']);
    }

    /** @var string */
    protected $id;

    /** @var ?int */
    protected $position;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response($this);
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
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
