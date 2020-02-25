<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Command;

use steevanb\PhpUrlTest\{
    Configuration\Dumper\ConsoleConfigurationDumper,
    CreateUrlTestServiceTrait
};
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};

class DumpConfigurationCommand extends Command
{
    use CreateUrlTestServiceTrait;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('urltest:configuration:dump')
            ->addOption(
                'configuration',
                'conf',
                InputOption::VALUE_OPTIONAL,
                'Configuration file name'
            )
            ->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Set recursive if path is a directory.', 'true')
            ->addArgument('path', InputArgument::REQUIRED, 'Configuration file name, or directories separated by ",".')
            ->addArgument('ids', InputArgument::OPTIONAL, 'UrlTest identifiers preg pattern to test.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('ids') === null ? null : explode(',', $input->getArgument('ids'));
        $urlTestService = $this->createUrlTestService(
            $input->getArgument('path'),
            $input->getOption('recursive') === 'true',
            $input->getOption('configuration')
        );

        $dumper = (new ConsoleConfigurationDumper($output))->dumpConfiguration($urlTestService, $ids);

        foreach ($urlTestService->getTests($ids ?? []) as $urlTest) {
            $output->writeln('');
            $dumper->dumpUrlTest($urlTest);
        }

        return 0;
    }
}
