<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Command;

use Symfony\Component\Console\{
    Command\Command,
    Helper\ProgressBar,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};
use steevanb\PhpUrlTest\{
    CreateUrlTestServiceTrait,
    ResponseComparator\ResponseComparatorInterface,
    ResponseComparator\ResponseComparatorService,
    UrlTest,
    UrlTestService
};

class UrlTestCommand extends Command
{
    use CreateUrlTestServiceTrait;

    /** @var ProgressBar */
    protected $progressBar;

    /** @var int */
    protected $urlTestSkipped = 0;

    /** @var int */
    protected $urlTestSuccess = 0;

    /** @var int */
    protected $urlTestFailed = 0;

    public function onProgress(UrlTest $urlTest): void
    {
        $urlTest->isValid() ? $this->urlTestSuccess++ : $this->urlTestFailed++;
        $this->progressBar->advance();
        $this->defineProgressBarMessage();
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('urltest:test')
            ->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Set parallel tests number.', 1)
            ->addOption(
                'comparator',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Comparator name to compare response with expected one'
            )
            ->addOption(
                'errorcomparator',
                'ec',
                InputOption::VALUE_OPTIONAL,
                'Comparator name to compare response with expected one when test fail',
                'console'
            )
            ->addOption(
                'configuration',
                'conf',
                InputOption::VALUE_OPTIONAL,
                'Configuration file name'
            )
            ->addOption('progress', null, InputOption::VALUE_OPTIONAL, 'Show/hide progress bar.', 'true')
            ->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Set recursive if path is a directory.', 'true')
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stop when a test fail.')
            ->addOption('continue', null, InputOption::VALUE_NONE, 'Start since last fail test.')
            ->addOption('skip', null, InputOption::VALUE_NONE, 'Skip last fail test, use it with --continue.')
            ->addArgument('path', InputArgument::REQUIRED, 'Configuration file name, or directories separated by ",".')
            ->addArgument('ids', InputArgument::OPTIONAL, 'UrlTest identifiers preg pattern to test.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ids = $input->getArgument('ids') === null ? null : explode(',', $input->getArgument('ids'));
        $service = $this
            ->createUrlTestService(
                $input->getArgument('path'),
                $input->getOption('recursive') === 'true',
                $input->getOption('configuration')
            )
            ->setStopOnError($input->getOption('stop-on-error'))
            ->setContinue($input->getOption('continue'), $input->getOption('skip'));
        if ($input->getOption('parallel') > 1) {
            $service
                ->setParallelNumber((int) $input->getOption('parallel'))
                ->setParallelResponseComparator($input->getOption('comparator'))
                ->setParallelResponseErrorComparator($input->getOption('errorcomparator'))
                ->setParallelVerbosity($this->getVerbosity($output));
        }

        $this->initProgressBar($output, $service, $ids, $input->getOption('progress') === 'true');
        $return = $service->executeTests($ids) === true ? 0 : 1;

        if ($input->getOption('parallel') <= 1) {
            $this->compareResponses(
                $service->getTests($ids),
                $input->getOption('comparator'),
                $input->getOption('errorcomparator'),
                $output
            );
        } else {
            $this->showParallelResponses($service->getTests($ids), $output);
        }

        if ($input->getOption('stop-on-error') && $service->hasContinueData()) {
            $output->writeln('');
            $output->writeln(
                "\e[43m\e[1;30m Tests stopped, use --continue to resume since last fail, "
                . "or --skip to resume after last fail. \e[00m"
            );
        }

        return $return;
    }

    protected function definePath(UrlTestService $urlTestService, string $path, bool $recursive): self
    {
        if (is_dir($path)) {
            $urlTestService->addTestDirectory($path, $recursive);
        } elseif (is_file($path)) {
            $urlTestService->addTestFile($path);
        } else {
            throw new \Exception('Invalid path or file name "' . $path . '".');
        }

        return $this;
    }

    protected function getResponseComparatorClassName(string $comparator): string
    {
        return substr($comparator, -10) !== 'ResponseComparator'
            ? 'steevanb\\PhpUrlTest\\ResponseComparator\\' . ucfirst($comparator) . 'ResponseComparator'
            : $comparator;
    }

    protected function compareResponses(
        array $urlTests,
        ?string $comparator,
        ?string $errorComparator,
        OutputInterface $output
    ): self {
        $comparatorService = new ResponseComparatorService();
        if ($comparator !== null) {
            $className = $this->getResponseComparatorClassName($comparator);
            $comparatorService
                ->addComparator($comparator, new $className())
                ->setDefaultComparatorId($comparator);
        }
        if ($errorComparator !== null) {
            $className = $this->getResponseComparatorClassName($errorComparator);
            $comparatorService
                ->addComparator($errorComparator, new $className())
                ->setDefaultErrorComparatorId($errorComparator);
        }
        $verbosity = $this->getVerbosity($output);

        foreach ($urlTests as $urlTest) {
            $comparatorService->compare($urlTest, $verbosity);
        }

        return $this;
    }

    /** @param UrlTest[] $urlTests */
    protected function showParallelResponses(array $urlTests, OutputInterface $output): self
    {
        foreach ($urlTests as $urlTest) {
            $output->write($urlTest->getParallelResponse());
        }

        return $this;
    }

    protected function getVerbosity(OutputInterface $output): int
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_NORMAL:
                $return = ResponseComparatorInterface::VERBOSITY_NORMAL;
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $return = ResponseComparatorInterface::VERBOSITY_VERBOSE;
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $return = ResponseComparatorInterface::VERBOSITY_VERY_VERBOSE;
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $return = ResponseComparatorInterface::VERBOSITY_DEBUG;
                break;
            default:
                throw new \Exception('Unknow verbosity "' . $output->getVerbosity() . '".');
        }

        return $return;
    }

    protected function initProgressBar(OutputInterface $output, UrlTestService $service, ?array $ids, bool $show): self
    {
        if ($show) {
            $this->progressBar = new ProgressBar($output, $service->countTests($ids));
            $this->progressBar->setFormat(
                '[%bar%] %current%/%max% %message% | %elapsed:6s%/%estimated:-6s% | %memory:6s%' . "\n"
            );
            $this->progressBar->start();

            $this->addSkippedTest($service->countSkippedTests());
            foreach ($service->getSuccessTests() as $urlTest) {
                $this->onProgress($urlTest);
            }
            foreach ($service->getFailedTests() as $urlTest) {
                $this->onProgress($urlTest);
            }

            $service->setOnProgressCallback([$this, 'onProgress']);
        }

        return $this;
    }

    protected function addSkippedTest(int $count = 1): self
    {
        $this->urlTestSkipped += $count;
        $this->defineProgressBarMessage();

        return $this;
    }

    protected function defineProgressBarMessage(): self
    {
        if ($this->progressBar instanceof ProgressBar) {
            $message = null;
            if ($this->urlTestSkipped > 0) {
                $message .= "\e[43m\e[1;30m " . $this->urlTestSkipped . " \e[00m ";
            }
            $message .= "\e[42m\e[1;37m " . $this->urlTestSuccess . " \e[00m";
            if ($this->urlTestFailed > 0) {
                $message .= " \e[41m\e[1;37m " . $this->urlTestFailed . " \e[00m";
            }
            $this->progressBar->setMessage($message);
            $this->progressBar->display();
        }

        return $this;
    }
}
