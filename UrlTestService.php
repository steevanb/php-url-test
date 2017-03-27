<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest;

use steevanb\PhpUrlTest\Configuration\Configuration;
use steevanb\PhpYaml\Parser;

class UrlTestService
{
    /** @var UrlTest[] */
    protected $tests = [];

    /** @var ?callable */
    protected $onProgressCallback;

    public function addTestDirectory(string $directory, bool $recursive = true): self
    {
        if (substr($directory, -1) !== DIRECTORY_SEPARATOR) {
            $directory = $directory . DIRECTORY_SEPARATOR;
        }
        $handle = opendir($directory);
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($entry) && $recursive) {
                $this->addTestDirectory($directory . $entry);
            } elseif (is_file($directory. $entry) && substr($entry, -12) === '.urltest.yml') {
                $this->addTestFile($directory . $entry);
            }
        }

        return $this;
    }

    public function addTestFile(string $fileName): self
    {
        Parser::registerFileFunction();
        foreach ((new Parser())->parse(file_get_contents($fileName)) as $id => $config) {
            $this->addTest($id, $this->createTest($id, $config));
        }

        return $this;
    }

    public function addTest(string $id, UrlTest $urlTest): self
    {
        if (isset($this->tests[$id])) {
            throw new \Exception('UrlTest id "' . $id . '" already exists.');
        }
        $this->tests[$id] = $urlTest;

        return $this;
    }

    /** @return UrlTest[] */
    public function getTests(): array
    {
        return $this->tests;
    }

    public function countTests(): int
    {
        return count($this->tests);
    }

    public function setOnProgressCallback(?callable $onProgressCallback): self
    {
        $this->onProgressCallback = $onProgressCallback;

        return $this;
    }

    public function getOnProgressCallback(): ?callable
    {
        return $this->onProgressCallback;
    }

    public function executeTests(): bool
    {
        $return = true;
        foreach ($this->getTests() as $id => $urlTest) {
            $urlTest->execute();

            if (is_callable($this->getOnProgressCallback())) {
                call_user_func_array($this->getOnProgressCallback(), [$id, $urlTest]);
            }

            if ($urlTest->isValid() === false) {
                $return = false;
            }
        }

        return $return;
    }

    protected function createTest(string $id, array $config): UrlTest
    {
        $return = new UrlTest($id);
        Configuration::create($config, $return);

        return $return;
    }
}
