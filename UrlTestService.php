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

    /** @var int */
    protected $parallelNumber = 1;

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
            if (is_dir($directory . $entry) && $recursive) {
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

    public function setParallelNumber(int $parallelNumber): self
    {
        $this->parallelNumber = $parallelNumber;

        return $this;
    }

    public function getParallelNumber(): int
    {
        return $this->parallelNumber;
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
        return ($this->getParallelNumber() > 1) ? $this->executeParallelTests() : $this->executeSequentialTests();
    }

    protected function executeSequentialTests(): bool
    {
        $return = true;
        foreach ($this->getTests() as $urlTest) {
            $urlTest->execute();

            if (is_callable($this->getOnProgressCallback())) {
                call_user_func($this->getOnProgressCallback(), $urlTest);
            }

            if ($urlTest->isValid() === false) {
                $return = false;
            }
        }

        return $return;
    }

    protected function executeParallelTests(): bool
    {
        $return = true;
        $tests = $this->getTests();
        $testIndex = 0;

        while (count($tests) > 0) {
            $processes = [];
            $count = min($this->getParallelNumber(), count($tests));
            for ($i = 0; $i < $count; $i++) {
                $processes[] = [
                    'urlTest' => array_shift($tests)
                ];
            }

            foreach ($processes as &$process) {
                $testIndex++;
                $pipes = 'pipes' . $testIndex;
                $process['process'] = proc_open(
                    'php ' . __DIR__ . '/bin/urltest.php --comparator=console --progress=false ../test.urltest.yml',
                    [
                        0 => ["pipe", "r"],
                        1 => ["pipe", "w"],
                        2 => ["file", "/tmp/error-output.txt", "a"]
                    ],
                    $$pipes
                );

                $process['pipes'] = &$$pipes;
            }

            foreach ($processes as &$process) {
                echo stream_get_contents($process['pipes'][1]);
                fclose($process['pipes'][1]);
                proc_close($process['process']);
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
