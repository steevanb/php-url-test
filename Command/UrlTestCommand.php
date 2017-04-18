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
    ResponseComparator\ResponseComparatorInterface,
    ResponseComparator\ResponseComparatorService,
    UrlTest,
    UrlTestService
};

class UrlTestCommand extends Command
{
    /** @var ProgressBar */
    protected $progressBar;

    /** @var int */
    protected $urlTestSuccess = 0;

    /** @var int */
    protected $urlTestFail = 0;

    public function onProgress(UrlTest $urlTest)
    {
        $urlTest->isValid() ? $this->urlTestSuccess++ : $this->urlTestFail++;
        $message = "\e[42m\e[1;37m " . $this->urlTestSuccess ." \e[00m";
        if ($this->urlTestFail > 0) {
            $message .= " \e[41m\e[1;37m " . $this->urlTestFail ." \e[00m";
        }
        $this->progressBar->setMessage($message);
        $this->progressBar->advance();
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('urltest')
            ->addOption('autoload', null, InputOption::VALUE_OPTIONAL, 'Set autoload file name.', null)
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
            ->addOption('progress', null, InputOption::VALUE_OPTIONAL, 'Show/hide progress bar.', 'true')
            ->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Set recursive if path is a directory.', 'true')
            ->addArgument('path', InputArgument::REQUIRED, 'Configuration file name, or directories separated by ",".')
            ->addArgument('ids', InputArgument::OPTIONAL, 'UrlTest identifiers preg pattern to test.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new UrlTestService();

        $this->definePath($service, $input->getArgument('path'), $input->getOption('recursive') === 'true');
        $service->setParallelNumber(intval($input->getOption('parallel')));

        $ids = $input->getArgument('ids') === null ? null : explode(',', $input->getArgument('ids'));
        if ($service->countTests($ids) === 0) {
            throw new \Exception('No test found.');
        }

        $this->initProgressBar($output, $service, $ids, $input->getOption('progress') === 'true');
        $return = $service->executeTests($ids) === true ? 0 : 1;
        $this->finishProgressBar();

        if ($input->getOption('parallel') <= 1) {
            $this->compareResponses(
                $service->getTests($ids),
                $input->getOption('comparator'),
                $input->getOption('errorcomparator'),
                $output
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
            $this->progressBar->setMessage("\e[42m\e[1;37m 0 \e[00m");
            $this->progressBar->start();
            $service->setOnProgressCallback([$this, 'onProgress']);
        }

        return $this;
    }

    protected function finishProgressBar(): self
    {
        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->finish();
        }

        return $this;
    }
}
