<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseComparator;

use steevanb\PhpUrlTest\Test\UrlTest;

class ConsoleResponseComparator implements ResponseComparatorInterface
{
    public function compare(UrlTest $urlTest, int $verbose): ResponseComparatorInterface
    {
        $this
            ->writeDiff(
                'Url',
                $urlTest->getExpectedResponse()->getUrl(),
                $urlTest->getResponse()->getUrl(),
                $verbose
            )
            ->writeDiff(
                'Http code',
                $urlTest->getExpectedResponse()->getCode(),
                $urlTest->getResponse()->getCode(),
                $verbose
            )
            ->writeDiff(
                'Connections',
                $urlTest->getExpectedResponse()->getNumConnects(),
                $urlTest->getResponse()->getNumConnects(),
                $verbose
            )
            ->writeDiff(
                'Size',
                $urlTest->getExpectedResponse()->getSize(),
                $urlTest->getResponse()->getSize(),
                $verbose
            )
            ->writeDiff(
                'Content type',
                $urlTest->getExpectedResponse()->getContentType(),
                $urlTest->getResponse()->getContentType(),
                $verbose
            )
            ->writeDiff(
                'Header size',
                $urlTest->getExpectedResponse()->getHeaderSize(),
                $urlTest->getResponse()->getHeaderSize(),
                $verbose
            )
            ->writeDiff(
                'Body size',
                $urlTest->getExpectedResponse()->getBodySize(),
                $urlTest->getResponse()->getBodySize(),
                $verbose
            )
            ->writeBodyDiff($urlTest)
            ->writeRedirectionDiff($urlTest, $verbose)
            ->writeRedirectionCountDiff($urlTest, $verbose)
            ->writeResult($urlTest);

        return $this;
    }

    protected function writeRedirectionDiff(UrlTest $urlTest, int $verbose): self
    {
        if ($urlTest->isAllowRedirect() === false) {
            echo 'Redirection: ';
            if ($urlTest->getResponse()->getCode() === 302) {
                $this->writeExpectedValue('not allowed');
                echo ', but http code ';
                $this->writeBadValue(302);
                echo ' returned';
            } else {
                $this->writeOkValue('none');
            }
            echo "\n";
        } elseif ($verbose === ResponseComparatorInterface::VERBOSE_HIGH) {
            echo 'Redirection: ';
            echo ($urlTest->getResponse()->getRedirectCount() === 0)
                ? 'none' :
                $urlTest->getResponse()->getRedirectCount();
            echo "\n";
        }

        return $this;
    }

    protected function writeRedirectionCountDiff(UrlTest $urlTest, int $verbose): self
    {
        if ($urlTest->getRedirectCount() !== null) {
            $this->writeDiff(
                'Redirections count',
                $urlTest->getRedirectCount(),
                $urlTest->getResponse()->getRedirectCount(),
                $verbose
            );
        } elseif ($urlTest->getRedirectMin() !== null || $urlTest->getRedirectMax() !== null) {
            echo 'Redirection count: ';
            if ($urlTest->getRedirectMin() === null) {
                $redirectionLabel = 'max ' . $urlTest->getRedirectMax();
                $isError = $urlTest->getRedirectMax() < $urlTest->getResponse()->getRedirectCount();
            } elseif ($urlTest->getRedirectMax() === null) {
                $redirectionLabel = 'min ' . $urlTest->getRedirectMin();
                $isError = $urlTest->getRedirectMin() > $urlTest->getResponse()->getRedirectCount();
            } else {
                $redirectionLabel = 'between ' . $urlTest->getRedirectMin() . ' and ' . $urlTest->getRedirectMax();
                $isError =
                    $urlTest->getRedirectMax() < $urlTest->getResponse()->getRedirectCount()
                    || $urlTest->getRedirectMin() > $urlTest->getResponse()->getRedirectCount();
            }
            if ($isError) {
                $this->writeExpectedValue($redirectionLabel);
                echo ', got ';
                $this->writeBadValue($urlTest->getResponse()->getRedirectCount());
            } else {
                echo $redirectionLabel;
            }
            echo "\n";
        } elseif ($verbose === ResponseComparatorInterface::VERBOSE_HIGH) {
            echo 'Redirections count: ' . $urlTest->getResponse()->getRedirectCount() . "\n";
        }

        return $this;
    }

    protected function writeBodyDiff(UrlTest $urlTest): self
    {
        if ($urlTest->getExpectedResponse()->getBody() !== null) {
            echo 'Body: ';
            if ($urlTest->getExpectedResponse()->getBody() === $urlTest->getResponse()->getBody()) {
                $this->writeOkValue('ok');
            } else {
                $this->writeBadValue('fail');
            }
            echo "\n";
        }

        return $this;
    }

    protected function writeDiff($label, $expectedValue, $value, int $verbose): self
    {
        if ($expectedValue !== null) {
            echo $label . ': ';
            if ($expectedValue !== $value) {
                echo 'expected ';
                $this->writeExpectedValue($expectedValue);
                echo ', got ';
                $this->writeBadValue($value);
            } else {
                $this->writeOkValue($value);
            }
            echo "\n";
        } elseif ($expectedValue === null && $verbose === ResponseComparatorInterface::VERBOSE_HIGH) {
            echo $label . ': ' . $value;
            echo "\n";
        }

        return $this;
    }

    protected function writeOkValue($value): self
    {
        echo "\033[32m" . $value . "\033[00m";

        return $this;
    }

    protected function writeExpectedValue($value): self
    {
        echo "\033[33m" . $value . "\033[00m";

        return $this;
    }

    protected function writeBadValue($value): self
    {
        echo "\033[31m" . $value . "\033[00m";

        return $this;
    }

    protected function writeResult(UrlTest $urlTest)
    {
        if ($urlTest->isValid()) {
            echo "\033[42m\033[1;37m OK \033[0m";
        } else {
            echo "\033[41m\033[1;37m FAIL \033[0m";
        }

        echo ' ' . $urlTest->getResponse()->getTime() . 'ms' . "\n";
    }
}
