<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResultReader;

class ResultReaderService
{
    public const VERBOSITY_QUIET = 0;
    public const VERBOSITY_NORMAL = 1;
    public const VERBOSITY_VERBOSE = 2;
    public const VERBOSITY_VERY_VERBOSE = 3;
    public const VERBOSITY_DEBUG = 4;

    /** @var ResultReaderInterface[] */
    protected $readers = [];

    public function addReader(string $className, bool $showSuccess, bool $showError, int $verbosity): self
    {
        $reader = new $className();
        if ($reader instanceof ResultReaderInterface === false) {
            throw new \Exception(
                'Result reader "' . $className . '" should implement "' . ResultReaderInterface::class . '".'
            );
        }

        $this->readers[] = [
            'reader' => $reader,
            'showSuccess' => $showSuccess,
            'showError' => $showError,
            'verbosity' => $verbosity
        ];

        return $this;
    }

    public function read(array $urlTests): self
    {
        foreach ($this->readers as $reader) {
            $reader['reader']->read($urlTests, $reader['showSuccess'], $reader['showError'], $reader['verbosity']);
        }

        return $this;
    }
}
