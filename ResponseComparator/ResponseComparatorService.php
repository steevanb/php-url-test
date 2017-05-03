<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResponseComparator;

use steevanb\PhpUrlTest\UrlTest;

class ResponseComparatorService
{
    /** @var ResponseComparatorInterface[] */
    protected $comparators = [];

    /** @var ?string */
    protected $defaultComparatorId;

    /** @var ?string */
    protected $defaultErrorComparatorId;

    public function __construct()
    {
        $this->addComparator('console', new ConsoleResponseComparator());
    }

    public function addComparator($id, ResponseComparatorInterface $comparator): self
    {
        $this->comparators[$id] = $comparator;

        return $this;
    }

    public function getComparators(): array
    {
        return $this->comparators;
    }

    public function setDefaultComparatorId(?string $defaultComparatorId): self
    {
        $this->defaultComparatorId = $defaultComparatorId;

        return $this;
    }

    public function getDefaultComparatorId(): ?string
    {
        return $this->defaultComparatorId;
    }

    public function getComparator(string $id = null): ?ResponseComparatorInterface
    {
        $id = $id ?? $this->getDefaultComparatorId();

        return $id === null ? null : $this->comparators[$this->getDefaultComparatorId()];
    }

    public function setDefaultErrorComparatorId(?string $defaultErrorComparatorId): self
    {
        $this->defaultErrorComparatorId = $defaultErrorComparatorId;

        return $this;
    }

    public function getDefaultErrorComparatorId(): ?string
    {
        return $this->defaultErrorComparatorId;
    }

    public function getErrorComparator(string $id = null): ?ResponseComparatorInterface
    {
        $id = $id ?? $this->getDefaultErrorComparatorId();

        return $id === null ? null : $this->comparators[$this->getDefaultErrorComparatorId()];
    }

    public function compare(
        UrlTest $urlTest,
        int $verbosity = ResponseComparatorInterface::VERBOSITY_NORMAL,
        string $comparatorId = null,
        string $errorComparatorId = null
    ): self {
        $comparator = $this->getComparator($comparatorId);
        $errorComparator = $this->getErrorComparator($errorComparatorId);

        if ($urlTest->isExecuted()) {
            if (
                (
                    $urlTest->isValid()
                    && $comparator instanceof ResponseComparatorInterface
                ) || (
                    $urlTest->isValid() === false
                    && $comparator instanceof ResponseComparatorInterface
                    && $errorComparator === null
                )
            ) {
                $comparator->compare($urlTest, $verbosity);
            } elseif (
                $urlTest->isValid() === false
                && $errorComparator instanceof ResponseComparatorInterface
            ) {
                $errorComparator->compare($urlTest, $verbosity);
            }
        }

        return $this;
    }
}
