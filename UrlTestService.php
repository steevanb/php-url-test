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

    /** @var ?string */
    protected $binAutoload;

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
        if (file_exists($fileName) === false) {
            throw new \Exception('UrlTest file "' . $fileName . '" does not exists.');
        }

        Parser::registerFileFunction(dirname($fileName));
        try {
            $configurations = (new Parser())->parse(file_get_contents($fileName));
        } catch (\Exception $exception) {
            throw new \Exception('[' . $fileName . '] ' . $exception->getMessage(), 0, $exception);
        }
        if (is_array($configurations) === false) {
            throw new \Exception('Url test configuration file "' . $fileName . '" is malformed.');
        }
        if (array_key_exists('_default', $configurations)) {
            $defaultConfiguration = $this->createConfiguration($configurations['_default'], $fileName, '_default');
            unset($configurations['_default']);
        } else {
            $defaultConfiguration = new Configuration();
        }

        foreach ($configurations as $id => $data) {
            $id = (string) $id;
            $urlTest = new UrlTest($id, $this->createConfiguration($data, $fileName, $id, $defaultConfiguration));
            $this
                ->addTest($id, $urlTest)
                ->addResponseBodyTransformer(
                    $urlTest->getConfiguration()->getResponse()->getBodyTransformerName(),
                    $urlTest
                )
                ->addResponseBodyTransformer(
                    $urlTest->getConfiguration()->getResponse()->getRealResponseBodyTransformerName(),
                    $urlTest
                );
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

    /**
     * @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve
     * @return UrlTest[]
     */
    public function getTests(array $ids = null): array
    {
        if ($ids === null) {
            $return = $this->tests;
        } else {
            $return = [];
            foreach ($this->tests as $test) {
                foreach ($ids as $id) {
                    $isPreg = preg_match('/^[a-zA-Z0-9_]{1}$/', $id[0]) === 0;
                    if ($isPreg) {
                        $match = preg_match($id, $test->getId());
                        if ($match === false) {
                            throw new \Exception('Invalid UrlTest identifier preg pattern "' . $id . '".');
                        }
                        if ($match === 1) {
                            $return[] = $test;
                        }
                    } elseif ($id === $test->getId()) {
                        $return[] = $test;
                    }
                }
            }
        }

        return $return;
    }

    /** @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve */
    public function countTests(array $ids = null): int
    {
        return count($this->getTests($ids));
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

    public function setBinAutoload(?string $autoload): self
    {
        $this->binAutoload = $autoload;

        return $this;
    }

    public function getBinAutoload(): ?string
    {
        return $this->binAutoload;
    }

    /** @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve */
    public function executeTests(array $ids = null): bool
    {
        return ($this->getParallelNumber() > 1)
            ? $this->executeParallelTests($ids)
            : $this->executeSequentialTests($ids);
    }

    protected function executeSequentialTests(array $ids = null): bool
    {
        $return = true;
        foreach ($this->getTests($ids) as $urlTest) {
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

    protected function executeParallelTests(array $ids = null): bool
    {
        $return = true;
        $tests = $this->getTests($ids);
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

    protected function addResponseBodyTransformer(?string $bodyTransformer, UrlTest $urlTest): self
    {
        if (
            $bodyTransformer !== null
            && $urlTest->hasResponseBodyTransformer($bodyTransformer) === false
            && class_exists($bodyTransformer)
        ) {
            $urlTest->addResponseBodyTransformer($bodyTransformer, new $bodyTransformer());
        }

        return $this;
    }

    protected function createConfiguration(
        array $data,
        string $fileName,
        string $urlTestId,
        Configuration $defaultConfiguration = null
    ): Configuration {
        try {
            $return = Configuration::create($data, $defaultConfiguration);
        } catch (\Exception $exception) {
            throw new \Exception(
                '[' . $fileName . '#' . $urlTestId . '] ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        return $return;
    }
}
