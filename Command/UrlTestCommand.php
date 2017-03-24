<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Command;

use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Output\OutputInterface
};
use steevanb\PhpUrlTest\{
    Configuration\Configuration,
    ResponseComparator\ConsoleResponseComparator,
    ResponseComparator\ResponseComparatorInterface, UrlTest
};

class UrlTestCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('urltest')
            ->addArgument('path', InputArgument::REQUIRED, 'Configuration file name, or directories separated by ",".');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urlTest = new UrlTest();
        $urlTest->setConfiguration(Configuration::createFromYaml($input->getFirstArgument(), $urlTest));
        $urlTest->execute();

        if ($output->getVerbosity() !== OutputInterface::VERBOSITY_QUIET) {
            $comparator = new ConsoleResponseComparator();
            $comparator->compare($urlTest, $this->getVerbosity($output));
        }
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
}
