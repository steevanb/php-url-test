<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseComparator;

use steevanb\PhpUrlTest\UrlTest;

class ConsoleResponseComparator implements ResponseComparatorInterface
{
    public function compare(UrlTest $urlTest, int $verbosity): ResponseComparatorInterface
    {
        $responseConfiguration = $urlTest->getConfiguration()->getResponse();
        $this
            ->writeDiff(
                'Url',
                $responseConfiguration->getUrl(),
                $urlTest->getResponse()->getUrl(),
                $verbosity
            )
            ->writeDiff(
                'Http code',
                $responseConfiguration->getCode(),
                $urlTest->getResponse()->getCode(),
                $verbosity
            )
            ->writeDiff(
                'Connections',
                $responseConfiguration->getNumConnects(),
                $urlTest->getResponse()->getNumConnects(),
                $verbosity
            )
            ->writeDiff(
                'Size',
                $responseConfiguration->getSize(),
                $urlTest->getResponse()->getSize(),
                $verbosity
            )
            ->writeDiff(
                'Content type',
                $responseConfiguration->getContentType(),
                $urlTest->getResponse()->getContentType(),
                $verbosity
            )
            ->writeDiff(
                'Header size',
                $responseConfiguration->getHeaderSize(),
                $urlTest->getResponse()->getHeaderSize(),
                $verbosity
            )
            ->writeDiff(
                'Body size',
                $responseConfiguration->getBodySize(),
                $urlTest->getResponse()->getBodySize(),
                $verbosity
            )
            ->writeBodyDiff($urlTest, $verbosity)
            ->writeRedirectionDiff($urlTest, $verbosity)
            ->writeRedirectionCountDiff($urlTest, $verbosity)
            ->writeResult($urlTest);

        return $this;
    }

    protected function writeRedirectionDiff(UrlTest $urlTest, int $verbosity): self
    {
        if ($urlTest->getConfiguration()->getRequest()->isAllowRedirect() === false) {
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
        } elseif ($verbosity >= ResponseComparatorInterface::VERBOSITY_VERY_VERBOSE) {
            echo 'Redirection: ';
            echo ($urlTest->getResponse()->getRedirectCount() === 0)
                ? 'none' :
                $urlTest->getResponse()->getRedirectCount();
            echo "\n";
        }

        return $this;
    }

    protected function writeRedirectionCountDiff(UrlTest $urlTest, int $verbosity): self
    {
        $responseConfiguration = $urlTest->getConfiguration()->getResponse();
        if ($responseConfiguration->getRedirectCount() !== null) {
            $this->writeDiff(
                'Redirections count',
                $responseConfiguration->getRedirectCount(),
                $urlTest->getResponse()->getRedirectCount(),
                $verbosity
            );
        } elseif (
            $responseConfiguration->getRedirectMin() !== null
            || $responseConfiguration->getRedirectMax() !== null
        ) {
            echo 'Redirection count: ';
            if ($responseConfiguration->getRedirectMin() === null) {
                $redirectionLabel = 'max ' . $responseConfiguration->getRedirectMax();
                $isError = $responseConfiguration->getRedirectMax() < $urlTest->getResponse()->getRedirectCount();
            } elseif ($responseConfiguration->getRedirectMax() === null) {
                $redirectionLabel = 'min ' . $responseConfiguration->getRedirectMin();
                $isError = $responseConfiguration->getRedirectMin() > $urlTest->getResponse()->getRedirectCount();
            } else {
                $redirectionLabel =
                    'between ' . $responseConfiguration->getRedirectMin()
                    . ' and ' . $responseConfiguration->getRedirectMax();
                $isError =
                    $responseConfiguration->getRedirectMax() < $urlTest->getResponse()->getRedirectCount()
                    || $responseConfiguration->getRedirectMin() > $urlTest->getResponse()->getRedirectCount();
            }
            if ($isError) {
                $this->writeExpectedValue($redirectionLabel);
                echo ', got ';
                $this->writeBadValue($urlTest->getResponse()->getRedirectCount());
            } else {
                echo $redirectionLabel;
            }
            echo "\n";
        } elseif ($verbosity >= ResponseComparatorInterface::VERBOSITY_VERY_VERBOSE) {
            echo 'Redirections count: ' . $urlTest->getResponse()->getRedirectCount() . "\n";
        }

        return $this;
    }

    protected function writeBodyDiff(UrlTest $urlTest, int $verbosity): self
    {
        $responseConfiguration = $urlTest->getConfiguration()->getResponse();
        if ($responseConfiguration->getBody() !== null) {
            echo 'Body: ';
            if (
                $responseConfiguration->getTransformedBody() === $urlTest->getResponse()->getTransformedBody()
            ) {
                $this->writeOkValue('ok');
            } else {
                $this->writeBadValue('fail');
            }
            echo "\n";
            if ($verbosity === ResponseComparatorInterface::VERBOSITY_DEBUG) {
                if ($responseConfiguration->getBody() !== $urlTest->getResponse()->getBody()) {
                    echo 'Expected body: ' . $responseConfiguration->getBody() . "\n";
                }
                echo 'Response body: ' . $urlTest->getResponse()->getBody() . "\n";
            }
        } elseif ($verbosity === ResponseComparatorInterface::VERBOSITY_DEBUG) {
            echo 'Body: ' . $urlTest->getResponse()->getBody();
        }

        return $this;
    }

    protected function writeDiff($label, $expectedValue, $value, int $verbosity): self
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
        } elseif ($expectedValue === null && $verbosity >= ResponseComparatorInterface::VERBOSITY_VERY_VERBOSE) {
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
