<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest;

use steevanb\PhpUrlTest\{
    Configuration\Exporter\YamlExporter,
    Configuration\Configuration
};
use steevanb\PhpYaml\Parser;
use Symfony\Component\Filesystem\Filesystem;

class UrlTestService
{
    /** @var string[] */
    protected $configurationFileNames = [];

    /** @var string[] */
    protected $directories = [];

    /** @var string[] */
    protected $files = [];

    /** @var UrlTest[] */
    protected $tests = [];

    /** @var array */
    protected $abstractTests = [];

    /** @var string[] */
    protected $skippedTests = [];

    /** @var ?callable */
    protected $onProgressCallback;

    /** @var int */
    protected $parallelNumber = 1;

    /** @var int */
    protected $parallelVerbosity = 0;

    /** @var ?string */
    protected $parallelResponseComparator;

    /** @var ?string */
    protected $parallelResponseErrorComparator;

    /** @var bool */
    protected $stopOnError = false;

    /** @var ?array */
    protected $continueData;

    /** @var array */
    protected $parameters = [];

    /** @var string */
    protected $varPath;

    public function __construct()
    {
        $this->varPath = sys_get_temp_dir();
    }

    public function addConfigurationFile(string $fileName): self
    {
        if (file_exists($fileName) === false) {
            throw new \Exception('Configuration file "' . $fileName . '" does not exist.');
        }
        $this->configurationFileNames[] = $fileName;

        Parser::registerFileFunction(dirname($fileName));
        try {
            $configurations = (new Parser())->parse(file_get_contents($fileName));
        } catch (\Exception $exception) {
            throw new \Exception('[' . $fileName . '] ' . $exception->getMessage(), 0, $exception);
        }

        foreach ($configurations['imports'] ?? [] as $import) {
            $this->addConfigurationFile(dirname($fileName) . DIRECTORY_SEPARATOR . $import['resource']);
        }

        foreach ($configurations['urltest'] ?? [] as $id => $configuration) {
            // parenthesis for a phpcs bug who interpret it as yoda condition
            if (($configuration['abstract'] ?? false) === true) {
                $this->addAbstractTest($id, $configuration);
            } else {
                $this->addTest($id, new UrlTest($id, $this->createConfiguration($configuration, $fileName, $id)));
            }
        }

        foreach ($configurations['parameters'] ?? [] as $name => $value) {
            $this->addParameter($name, $value);
        }

        return $this;
    }

    public function getConfigurationFileNames(): array
    {
        return $this->configurationFileNames;
    }

    public function addParameter(string $name, $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDirectories(): array
    {
        return array_keys($this->directories);
    }

    public function getFiles(): array
    {
        return array_keys($this->files);
    }

    public function setStopOnError(bool $stop)
    {
        $this->stopOnError = $stop;

        return $this;
    }

    public function isStopOnError(): bool
    {
        return $this->stopOnError;
    }

    public function addTestDirectory(string $directory, bool $recursive = true): self
    {
        $this->addTestDirectoryAndRegisterDirectory(realpath($directory), $recursive);

        return $this;
    }

    public function setVarPath(string $directory): self
    {
        $this->varPath = $directory;

        return $this;
    }

    public function getVarPath(): string
    {
        return $this->varPath;
    }

    public function addTestFile(string $fileName): self
    {
        if (file_exists($fileName) === false) {
            throw new \Exception('UrlTest file "' . $fileName . '" does not exists.');
        }
        $fileName = realpath($fileName);
        $this->files[$fileName] = null;

        Parser::registerFileFunction(dirname($fileName));
        try {
            $configurations = (new Parser())->parse(file_get_contents($fileName));
        } catch (\Exception $exception) {
            throw new \Exception('[' . $fileName . '] ' . $exception->getMessage(), 0, $exception);
        }

        if ($configurations !== null) {
            if (is_array($configurations) === false) {
                throw new \Exception('Url test configuration file "' . $fileName . '" is malformed.');
            }
            $defaultConfiguration['expectedResponse']['code'] = 200;
            if (array_key_exists('_defaults', $configurations)) {
                $defaultConfiguration = $configurations['_defaults'];
                unset($configurations['_defaults']);
            }

            foreach ($configurations as $id => $data) {
                $this->assertTestId($id);
                $id = (string) $id;
                $this->addTest(
                    $id,
                    new UrlTest($id, $this->createConfiguration($data, $fileName, $id, $defaultConfiguration))
                );
            }
        }

        return $this;
    }

    public function addTest(string $id, UrlTest $urlTest): self
    {
        if (isset($this->tests[$id])) {
            throw new \Exception('UrlTest id "' . $id . '" already exists.');
        }
        $this->tests[$id] = $urlTest;
        ksort($this->tests);

        $this
            ->addResponseBodyTransformer(
                $urlTest->getConfiguration()->getResponse()->getBodyTransformerName(),
                $urlTest
            )
            ->addResponseBodyTransformer(
                $urlTest->getConfiguration()->getResponse()->getRealResponseBodyTransformerName(),
                $urlTest
            );

        return $this;
    }

    /**
     * @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve
     * @return UrlTest[]
     */
    public function getTests(array $ids = null, bool $skipSkipped = true): array
    {
        $return = [];
        $skipped = ($skipSkipped) ? $this->getSkippedTests() : [];
        foreach ($this->tests as $test) {
            foreach ($skipped as $skip) {
                if ($skip->getId() === $test->getId()) {
                    continue 2;
                }
            }

            if ($ids === null) {
                $return[] = $test;
            } else {
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

        return $this->sortTests($return);
    }

    public function addAbstractTest(string $id, array $configuration): self
    {
        if (isset($this->abstractTests[$id])) {
            throw new \Exception('Abstract UrlTest id "' . $id . '" already exists.');
        }
        $this->abstractTests[$id] = $configuration;

        return $this;
    }

    public function getAbstractTest(string $id): array
    {
        if (array_key_exists($id, $this->abstractTests) === false) {
            throw new \Exception('Abstract UrlTest id "' . $id . '" not found.');
        }

        return $this->abstractTests[$id];
    }

    public function addSkippedTest(string $id): self
    {
        $this->skippedTests[] = $id;

        return $this;
    }

    public function getSkippedTests()
    {
        return $this->getTests($this->skippedTests, false);
    }

    public function isSkippedTest(string $id): bool
    {
        $return = false;
        foreach ($this->getSkippedTests() as $skippedTest) {
            if ($skippedTest->getId() === $id) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    public function countSkippedTests(): int
    {
        return count($this->getSkippedTests());
    }

    /** @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve */
    public function countTests(array $ids = null): int
    {
        return count($this->getTests($ids));
    }

    /** @return UrlTest[] */
    public function getFailedTests(): array
    {
        $return = [];
        foreach ($this->getTests() as $urlTest) {
            if (
                ($urlTest->isExecuted() && $urlTest->isValid() === false)
                || in_array($urlTest->getId(), $this->continueData['failed'])
            ) {
                $return[] = $urlTest;
            }
        }

        return $return;
    }

    public function countFailTests(): int
    {
        return count($this->getFailedTests());
    }

    /** @return UrlTest[] */
    public function getSuccessTests(): array
    {
        $return = [];
        foreach ($this->getTests() as $urlTest) {
            if (
                ($urlTest->isExecuted() && $urlTest->isValid())
                || in_array($urlTest->getId(), $this->continueData['success'])
            ) {
                $return[] = $urlTest;
            }
        }

        return $return;
    }

    public function countSuccessTests(): int
    {
        return count($this->getSuccessTests());
    }

    /** @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve */
    public function isAllTestsExecuted(array $ids = null): bool
    {
        $return = true;
        foreach ($this->getTests($ids) as $urlTest) {
            if (
                $this->isSkippedTest($urlTest->getId())
                || in_array($urlTest->getId(), $this->continueData['success'])
                || in_array($urlTest->getId(), $this->continueData['failed'])
            ) {
                continue;
            }
            if ($urlTest->isExecuted() === false) {
                $return = false;
                break;
            }
        }

        return $return;
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

    public function setParallelVerbosity(int $level): self
    {
        if ($level < 0 || $level > 3) {
            throw new \Exception('Parallel verbosityy must be between 0 and 3.');
        }
        $this->parallelVerbosity = $level;

        return $this;
    }

    public function getParallelVerbosity(): int
    {
        return $this->parallelVerbosity;
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

    public function setContinue(bool $continue = true, bool $skip = false): self
    {
        foreach ($this->getTests() as $urlTest) {
            if ($urlTest->isExecuted()) {
                throw new \Exception(
                    'Can\'t change continue because test "' . $urlTest->getId() . '" is alreayd executed.'
                );
            }
        }

        if ($continue === true) {
            $continueFilePath = $this->getContinueFilePath();
            if (is_readable($continueFilePath) === false) {
                throw new \Exception(
                    'Continue file "' . $continueFilePath . '" does not exist or is not readable. '
                    . 'Maybe your last tests were not stopped by a fail?'
                );
            }
            $this->continueData = require($continueFilePath);

            foreach ($this->continueData['skipped'] as $id) {
                if (count($this->getTests([$id])) === 0) {
                    throw new \Exception('Skipped test "' . $id . '" does not exist.');
                }
                $this->addSkippedTest($id);
            }

            foreach ($this->continueData['success'] as $id) {
                $tests = $this->getTests([$id]);
                if (count($tests) === 0) {
                    throw new \Exception('Successfull test "' . $id . '" does not exist.');
                }
                $tests[0]->setValid(true);
            }

            foreach ($this->continueData['failed'] as $id) {
                $tests = $this->getTests([$id]);
                if (count($tests) === 0) {
                    throw new \Exception('Failed test "' . $id . '" does not exist.');
                }
                $tests[0]->setValid(false);
            }

            if ($skip && $this->continueData['current'] !== null) {
                $this->addSkippedTest($this->continueData['current']);
            }
        } else {
            $this->continueData = ['skipped' => [], 'success' => [], 'failed' => [], 'current' => null];
            foreach ($this->getSuccessTests() as $urlTest) {
                $urlTest->setValid(null);
            }
            foreach ($this->getFailedTests() as $urlTest) {
                $urlTest->setValid(null);
            }
        }

        return $this;
    }

    public function setParallelResponseComparator(?string $parallelResponseComparator): self
    {
        $this->parallelResponseComparator = $parallelResponseComparator;

        return $this;
    }

    public function getParallelResponseComparator(): ?string
    {
        return $this->parallelResponseComparator;
    }

    public function setParallelResponseErrorComparator(?string $parallelResponseErrorComparator): self
    {
        $this->parallelResponseErrorComparator = $parallelResponseErrorComparator;

        return $this;
    }

    public function getParallelResponseErrorComparator(): ?string
    {
        return $this->parallelResponseErrorComparator;
    }

    /** @param string[]|null $ids UrlTest identifiers string or preg pattern to retrieve */
    public function executeTests(array $ids = null): bool
    {
        $continueFilePath = $this->getContinueFilePath();
        if (is_dir(dirname($continueFilePath)) === false) {
            (new Filesystem())->mkdir(dirname($continueFilePath));
        }
        if (file_exists($continueFilePath)) {
            unlink($continueFilePath);
        }

        return ($this->getParallelNumber() > 1)
            ? $this->executeParallelTests($ids)
            : $this->executeSequentialTests($ids);
    }

    public function addTestDirectoryAndRegisterDirectory(
        string $directory,
        bool $recursive = true,
        bool $register = true
    ): self {
        if (substr($directory, -1) !== DIRECTORY_SEPARATOR) {
            $directory = $directory . DIRECTORY_SEPARATOR;
        }
        if ($register) {
            $this->directories[$directory] = null;
        }

        $handle = opendir($directory);
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($directory . $entry) && $recursive) {
                $this->addTestDirectoryAndRegisterDirectory($directory . $entry, true, false);
            } elseif (is_file($directory . $entry) && substr($entry, -12) === '.urltest.yml') {
                $this->addTestFile($directory . $entry);
            }
        }

        return $this;
    }

    public function assertTestId(string $id): self
    {
        if (substr($id, 0, 1) === '_') {
            throw new \Exception(
                'UrlTest id "' . $id . '" should not begin with "_", it is reserved for configurations.'
            );
        }

        return $this;
    }

    public function hasContinueData(): bool
    {
        return file_exists($this->getContinueFilePath());
    }

    /** @param UrlTest[] $urlTests */
    protected function sortTests(array $urlTests): array
    {
        $sorted = [];
        foreach ($urlTests as $urlTest) {
            $position = $urlTest->getConfiguration()->getPosition() ?? -1;
            if (array_key_exists($position, $sorted) === false) {
                $sorted[$position] = [];
            }
            $sorted[$position][] = $urlTest;
        }
        ksort($sorted);

        $return = [];
        foreach ($sorted as $urlTests) {
            foreach ($urlTests as $urlTest) {
                $return[] = $urlTest;
            }
        }

        return $return;
    }

    protected function executeSequentialTests(array $ids = null): bool
    {
        $return = true;
        foreach ($this->getTests($ids) as $urlTest) {
            if (
                $urlTest->getId() !== $this->continueData['current']
                && (
                    in_array($urlTest->getId(), $this->continueData['success'])
                    || in_array($urlTest->getId(), $this->continueData['failed'])
                )
            ) {
                continue;
            }
            $urlTest->execute();

            if (is_callable($this->getOnProgressCallback())) {
                call_user_func($this->getOnProgressCallback(), $urlTest);
            }

            if ($urlTest->isValid() === false) {
                $return = false;
                if ($this->isStopOnError()) {
                    $this->saveContinueData($urlTest);
                    break;
                }
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
                $processes[] = ['urlTest' => array_shift($tests)];
            }

            foreach ($processes as &$process) {
                $testIndex++;
                $pipes = 'pipes' . $testIndex;
                $process['errorsFileName'] = tempnam(sys_get_temp_dir(), 'urltest_errors_');
                file_put_contents($process['errorsFileName'], null);
                $process['configurationFileName'] = $this->getTemporaryConfigurationFileName();
                $process['process'] = proc_open(
                    $this->getParallelCommand($process['urlTest'], $process['configurationFileName']),
                    [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['file', $process['errorsFileName'], 'a']
                    ],
                    $$pipes
                );

                $process['pipes'] = &$$pipes;
            }

            foreach ($processes as &$process) {
                $process['urlTest']->setParallelResponse(stream_get_contents($process['pipes'][1]));

                if (file_exists($process['configurationFileName'])) {
                    unlink($process['configurationFileName']);
                }

                if (is_readable($process['errorsFileName'])) {
                    $errors = file_get_contents($process['errorsFileName']);
                    unlink($process['errorsFileName']);
                    if ($errors !== '') {
                        $process['urlTest']->setParallelResponse(
                            $process['urlTest']->getParallelResponse()
                            . "\n\n"
                            . $errors
                        );
                    }
                }

                fclose($process['pipes'][1]);
                $process['urlTest']->setValid(proc_close($process['process']) === 0);

                if (is_callable($this->getOnProgressCallback())) {
                    call_user_func($this->getOnProgressCallback(), $process['urlTest']);
                }

                if ($process['urlTest']->isValid() === false) {
                    $return = false;
                    if ($this->isStopOnError()) {
                        $this->saveContinueData($process['urlTest']);
                    }
                }
            }

            if ($return === false && $this->isStopOnError()) {
                break;
            }
        }

        return $return;
    }

    protected function getParallelCommand(UrlTest $urlTest, string $configurationFileName): string
    {
        $return = 'php ' . $this->getUrlTestBinPath() . ' --progress=false ';
        if ($this->getParallelResponseComparator() !== null) {
            $return .= '--comparator=' . $this->getParallelResponseComparator() . ' ';
        }
        if ($this->getParallelResponseErrorComparator() !== null) {
            $return .= '--errorcomparator=' . $this->getParallelResponseErrorComparator() . ' ';
        }
        if ($this->getParallelVerbosity() > 0) {
            $return .= '-' . str_repeat('v', $this->getParallelVerbosity()) . ' ';
        }

        (new YamlExporter())->exportToFile($urlTest, $configurationFileName);
        $return .= $configurationFileName . ' ' . $urlTest->getId();

        return $return;
    }

    protected function getTemporaryConfigurationFileName(): string
    {
        return tempnam(sys_get_temp_dir(), 'urltest_test_');
    }

    protected function getUrlTestBinPath(): string
    {
        return __DIR__ . '/bin/urltest';
    }

    protected function saveContinueData(UrlTest $current): self
    {
        $this->continueData['current'] = $current->getId();

        foreach ($this->getSkippedTests() as $urlTest) {
            if (in_array($urlTest->getId(), $this->continueData['skipped']) === false) {
                $this->continueData['skipped'][] = $urlTest->getId();
            }
        }

        foreach ($this->getSuccessTests() as $urlTest) {
            if (in_array($urlTest->getId(), $this->continueData['success']) === false) {
                $this->continueData['success'][] = $urlTest->getId();
            }
        }

        foreach ($this->getFailedTests() as $urlTest) {
            if (
                $current->getId() !== $urlTest->getId()
                && in_array($urlTest->getId(), $this->continueData['failed']) === false
            ) {
                $this->continueData['failed'][] = $urlTest->getId();
            }
        }

        $content = '<?php' . "\n";
        $content .= 'return ' . var_export($this->continueData, true) . ';';
        file_put_contents($this->getContinueFilePath(), $content);

        return $this;
    }

    protected function getContinueFilePath(): string
    {
        return $this->getVarPath() . DIRECTORY_SEPARATOR . 'urltest.continue';
    }

    protected function addResponseBodyTransformer(?string $bodyTransformer, UrlTest $urlTest): self
    {
        if (
            $bodyTransformer !== null
            && $urlTest->hasResponseBodyTransformer($bodyTransformer) === false
        ) {
            if (class_exists($bodyTransformer) === false) {
                throw new \Exception('Body transformer class "' . $bodyTransformer . '" not found.');
            }
            $urlTest->addResponseBodyTransformer($bodyTransformer, new $bodyTransformer());
        }

        return $this;
    }

    protected function createConfiguration(
        array $data,
        string $fileName,
        string $urlTestId,
        array $defaultConfiguration = []
    ): Configuration {
        try {
            $parentConfiguration = [];
            if (array_key_exists('parent', $data)) {
                $parentConfiguration = $this->getAbstractTest($data['parent']);
            } elseif (array_key_exists('parent', $defaultConfiguration)) {
                $parentConfiguration = $this->getAbstractTest($defaultConfiguration['parent']);
            }

            $return = Configuration::create(
                $urlTestId,
                $data,
                $parentConfiguration,
                $defaultConfiguration,
                $this->getParameters()
            );
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
