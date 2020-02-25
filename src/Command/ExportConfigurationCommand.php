<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Command;

use steevanb\PhpUrlTest\{
    Configuration\Exporter\YamlExporter,
    CreateUrlTestServiceTrait
};
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};

class ExportConfigurationCommand extends Command
{
    use CreateUrlTestServiceTrait;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('urltest:configuration:export')
            ->addArgument('path', InputArgument::REQUIRED, 'Configuration file name')
            ->addArgument('id', InputArgument::REQUIRED, 'UrlTest identifier')
            ->addArgument('fileName', InputArgument::OPTIONAL, 'Yaml file name where configuration will be written')
            ->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Set recursive if path is a directory.', 'true');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->createUrlTestService(
            $input->getArgument('path'),
            $input->getOption('recursive') === 'true'
        );

        $tests = $service->getTests([$input->getArgument('id')]);
        if (count($tests) !== 1) {
            throw new \Exception('UrlTest "' . $input->getArgument('id') . '" not found.');
        }

        $exporter = new YamlExporter();
        if ($input->getArgument('fileName') !== null) {
            $exporter->exportToFile($tests[0], $input->getArgument('fileName'));
        } else {
            $output->write($exporter->export($tests[0]));
        }

        return 0;
    }
}
