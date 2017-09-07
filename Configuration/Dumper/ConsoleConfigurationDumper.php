<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Configuration\Dumper;

use Symfony\Component\Console\{
    Helper\Table,
    Output\OutputInterface
};
use steevanb\PhpUrlTest\{
    Configuration\Configuration,
    UrlTest,
    UrlTestService
};

class ConsoleConfigurationDumper
{
    /** @var OutputInterface */
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function dumpGlobal(UrlTestService $urlTestService, ?array $ids): self
    {
        $this->writeHeader('Global');
        $table = new Table($this->output);
        $table->setHeaders(['Global', 'Value']);
        $this->writeConfiguration($table, 'Tests', $urlTestService->countTests());
        if (count($ids ?? []) > 0) {
            $this->writeConfiguration($table, 'Filtered tests', $urlTestService->countTests($ids));
        }
        $this
            ->writeArrayConfiguration($table, 'Directory', 'Directories', $urlTestService->getDirectories())
            ->writeArrayConfiguration($table, 'File', 'Files', $urlTestService->getFiles());
        $table->render();

        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('');
            $countTests = $urlTestService->countTests();
            $this->writeHeader($countTests <= 1 ? $countTests . ' test' : $countTests . ' tests');
            $table = new Table($this->output);
            $table->setHeaders(['', 'Id', 'Method', 'Url']);
            foreach ($urlTestService->getTests() as $index => $urlTest) {
                $table->addRow([
                    '#' . ($index + 1),
                    $urlTest->getId(),
                    $urlTest->getConfiguration()->getRequest()->getMethod(),
                    $urlTest->getConfiguration()->getRequest()->getUrl()
                ]);
            }
            $table->render();
        }

        if (count($ids ?? []) > 0) {
            $this->output->writeln('');
            $table = new Table($this->output);
            $table->setHeaders(['Test id filter', 'Tests found']);
            foreach ($ids as $id) {
                $table->addRow([$id, $urlTestService->countTests([$id])]);
            }
            $table->render();
        }

        return $this;
    }

    public function dump(UrlTest $urlTest): void
    {
        $request = $urlTest->getConfiguration()->getRequest();
        $response = $urlTest->getConfiguration()->getResponse();

        $this->writeHeader('#' . $urlTest->getId());

        $table = new Table($this->output);
        $table->setHeaders(['Request', 'Value']);
        $this
            ->writeConfiguration($table, 'Url', $request->getUrl())
            ->writeConfiguration($table, 'Port', $request->getPort())
            ->writeConfiguration($table, 'Method', $request->getMethod())
            ->writeRequestRedirectionConfiguration($table, $urlTest->getConfiguration())
            ->writeHeadersConfiguration($table, 'Header', 'Headers',  $request->getHeaders())
            ->writeConfiguration($table, 'User-argent', $request->getUserAgent())
            ->writeConfiguration($table, 'Referer', $request->getReferer())
            ->writeConfiguration($table, 'Timeout', $request->getTimeout());
        $table->render();

        if ($request->getPostData() !== null) {
            $this->output->writeln('');
            $this->output->writeln('Request post data:');
            $this->output->writeln($request->getPostData());
        }

        $this->output->writeln('');
        $table = new Table($this->output);
        $table->setHeaders(['Response', 'Value']);
        $this
            ->writeConfiguration($table, 'Url', $response->getUrl())
            ->writeConfiguration($table, 'Http code', $response->getCode())
            ->writeConfiguration($table, 'Connections', $response->getNumConnects())
            ->writeConfiguration($table, 'Size', $response->getSize())
            ->writeConfiguration($table, 'Content type', $response->getContentType())
            ->writeConfiguration($table, 'Header size', $response->getHeaderSize())
            ->writeHeadersConfiguration($table, 'Allowed header', 'Allowed headers', $response->getHeaders())
            ->writeHeadersConfiguration($table, 'Unallowed header', 'Unallowed headers', $response->getUnallowedHeaders())
            ->writeConfiguration($table, 'Body size', $response->getBodySize())
            ->writeConfiguration($table, 'Body transformer', $response->getRealResponseBodyTransformerName())
            ->writeConfiguration($table, 'Body file name', $response->getRealResponseBodyFileName())
            ->writeConfiguration($table, 'Body comparison transformer', $response->getBodyTransformerName())
            ->writeConfiguration($table, 'Body comparison file name', $response->getBodyFileName())
            ->writeResponseRedirectionConfiguration($table, $urlTest->getConfiguration());
        $expectedBody = $urlTest->getTransformedBody(
            $response->getBody(),
            $response->getBodyTransformerName()
        );
        if ($expectedBody !== null && $this->output->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL) {
            $this->writeConfiguration($table, 'Body', '<comment>Add -v to show body.</comment>');
        }
        $table->render();

        if ($expectedBody !== null && $this->output->getVerbosity() > OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('');
            $this->output->writeln('Response body:');
            $this->output->writeln($expectedBody);
        }
    }

    protected function writeHeader(string $name): self
    {
        $this->output->writeln("<comment>$name</comment>");

        return $this;
    }

    protected function writeConfiguration(Table $table, string $name, $value): self
    {
        if ($value !== null) {
            $table->addRow([$name, $value]);
        }

        return $this;
    }

    protected function writeArrayConfiguration(
        Table $table,
        string $name,
        string $pluralName,
        ?array $configurations
    ): self {
        $index = 0;
        foreach ($configurations ?? [] as $configuration) {
            $table->addRow([
                $index === 0
                    ? (count($configurations) === 1 ? $name : $pluralName)
                    : null,
                $configuration
            ]);
            $index++;
        }

        return $this;
    }

    protected function writeHeadersConfiguration(Table $table, string $name, string $pluralName,?array $headers): self
    {
        $configurations = [];
        foreach ($headers ?? [] as $headerName => $headerValue) {
            $configurations[] = $headerName . ': ' . $headerValue;
        }

        return $this->writeArrayConfiguration($table, $name, $pluralName, $configurations);
    }

    protected function writeRequestRedirectionConfiguration(Table $table, Configuration $configuration): self
    {
        if ($configuration->getRequest()->isAllowRedirect()) {
            $table->addRow(['Redirection', 'Allowed']);
        } else {
            $table->addRow(['Redirection', 'Not allowed']);
        }

        return $this;
    }

    protected function writeResponseRedirectionConfiguration(Table $table, Configuration $configuration): self
    {
        if ($configuration->getResponse()->getRedirectMin() !== null) {
            $table->addRow(['Redirection min', $configuration->getResponse()->getRedirectMin()]);
        }
        if ($configuration->getResponse()->getRedirectMax() !== null) {
            $table->addRow(['Redirection count', $configuration->getResponse()->getRedirectMax()]);
        }
        if ($configuration->getResponse()->getRedirectCount() !== null) {
            $table->addRow(['Redirection count', $configuration->getResponse()->getRedirectCount()]);
        }

        return $this;
    }
}
