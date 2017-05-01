<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Command;

use steevanb\PhpUrlTest\Configuration\Dumper\ConsoleConfigurationDumper;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};

class DumpConfigurationCommand extends Command
{
    use CreateUrlTestService;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('urltest:configuration:dump')
            ->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Set recursive if path is a directory.', 'true')
            ->addArgument('path', InputArgument::REQUIRED, 'Configuration file name, or directories separated by ",".')
            ->addArgument('ids', InputArgument::OPTIONAL, 'UrlTest identifiers preg pattern to test.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('ids') === null ? null : explode(',', $input->getArgument('ids'));
        $service = $this->createUrlTestService(
            $input->getArgument('path'),
            $input->getOption('recursive') === 'true'
        );

        $dumper = new ConsoleConfigurationDumper();
        $dumper->setOutput($output);
        $dumper->dumpGlobal($service, $ids);
        foreach ($service->getTests($ids ?? []) as $urlTest) {
            $output->writeln('');
            $dumper->dump($urlTest);
        }

        return 0;
    }
}
