<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration\Exporter;

use steevanb\PhpUrlTest\UrlTest;
use Symfony\Component\Yaml\Yaml;

class YamlExporter
{
    protected $configuration = [];

    public function exportToFile(UrlTest $urlTest, string $fileName): self
    {
        if (file_put_contents($fileName, $this->export($urlTest)) === false) {
            throw new \Exception('Error while exporting urltest "' . $urlTest->getId() . '" into "' . $fileName . '".');
        }

        return $this;
    }

    public function export(UrlTest $urlTest): string
    {
        $this->configuration = [];

        $this
            ->defineConfiguration(['request'], 'url', $urlTest->getConfiguration()->getRequest()->getUrl())
            ->defineConfiguration(['request'], 'timeout', $urlTest->getConfiguration()->getRequest()->getTimeout())
            ->defineConfiguration(['request'], 'port', $urlTest->getConfiguration()->getRequest()->getPort())
            ->defineConfiguration(['request'], 'method', $urlTest->getConfiguration()->getRequest()->getMethod())
            ->defineConfiguration(['request'], 'userAgent', $urlTest->getConfiguration()->getRequest()->getUserAgent())
            ->defineConfiguration(['request'], 'referer', $urlTest->getConfiguration()->getRequest()->getReferer())
            ->defineConfiguration(
                ['request'],
                'allowRedirect',
                $urlTest->getConfiguration()->getRequest()->isAllowRedirect()
            )
            ->defineConfiguration(['request'], 'headers', $urlTest->getConfiguration()->getRequest()->getHeaders())
            ->defineConfiguration(['request'], 'postData', $urlTest->getConfiguration()->getRequest()->getPostData());

        $this
            ->defineConfiguration(['expectedResponse'], 'url', $urlTest->getConfiguration()->getResponse()->getUrl())
            ->defineConfiguration(['expectedResponse'], 'code', $urlTest->getConfiguration()->getResponse()->getCode())
            ->defineConfiguration(['expectedResponse'], 'size', $urlTest->getConfiguration()->getResponse()->getSize())
            ->defineConfiguration(
                ['expectedResponse'],
                'contentType',
                $urlTest->getConfiguration()->getResponse()->getContentType()
            )
            ->defineConfiguration(
                ['expectedResponse'],
                'numConnects',
                $urlTest->getConfiguration()->getResponse()->getNumConnects()
            )
            ->defineConfiguration(
                ['expectedResponse','redirect'],
                'min',
                $urlTest->getConfiguration()->getResponse()->getRedirectMin()
            )
            ->defineConfiguration(
                ['expectedResponse', 'redirect'],
                'max',
                $urlTest->getConfiguration()->getResponse()->getRedirectMax()
            )
            ->defineConfiguration(
                ['expectedResponse', 'redirect'],
                'count',
                $urlTest->getConfiguration()->getResponse()->getRedirectCount()
            )
            ->defineConfiguration(
                ['expectedResponse', 'header'],
                'size',
                $urlTest->getConfiguration()->getResponse()->getHeaderSize()
            )
            ->defineConfiguration(
                ['expectedResponse', 'header'],
                'size',
                $urlTest->getConfiguration()->getResponse()->getHeaderSize()
            )
            ->defineConfiguration(
                ['expectedResponse', 'header'],
                'headers',
                $urlTest->getConfiguration()->getResponse()->getHeaders()
            )
            ->defineConfiguration(
                ['expectedResponse', 'header'],
                'unallowedHeaders',
                $urlTest->getConfiguration()->getResponse()->getUnallowedHeaders()
            )
            ->defineConfiguration(
                ['expectedResponse', 'body'],
                'size',
                $urlTest->getConfiguration()->getResponse()->getBodySize()
            )
            ->defineConfiguration(
                ['expectedResponse', 'body'],
                'content',
                $urlTest->getConfiguration()->getResponse()->getBody()
            )
            ->defineConfiguration(
                ['expectedResponse', 'body'],
                'transformer',
                $urlTest->getConfiguration()->getResponse()->getBodyTransformerName()
            )
            ->defineConfiguration(
                ['expectedResponse', 'body'],
                'fileName',
                $urlTest->getConfiguration()->getResponse()->getBodyFileName()
            );

        $this
            ->defineConfiguration(
                ['response', 'body'],
                'transformer',
                $urlTest->getConfiguration()->getResponse()->getRealResponseBodyTransformerName()
            )
            ->defineConfiguration(
                ['response', 'body'],
                'fileName',
                $urlTest->getConfiguration()->getResponse()->getRealResponseBodyFileName()
            );

        return Yaml::dump([$urlTest->getId() => $this->configuration], 10);
    }

    protected function defineConfiguration(array $path, string $name, $value): self
    {
        $configuration = &$this->configuration;
        foreach ($path as $key) {
            if (array_key_exists($key, $configuration) === false) {
                $configuration[$key] = [];
            }
            $configuration = &$configuration[$key];
        }

        $configuration[$name] = $value;

        return $this;
    }
}
