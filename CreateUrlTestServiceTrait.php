<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest;

trait CreateUrlTestServiceTrait
{
    protected function createUrlTestService(
        string $path,
        bool $recursive,
        string $configurationFileName = null
    ): UrlTestService {
        $return = new UrlTestService();

        if (is_string($configurationFileName)) {
            $return->addConfigurationFile($configurationFileName);
        }

        if (is_dir($path)) {
            if (is_string($configurationFileName) === false) {
                $urltestFileName = trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'urltest.yml';
                if (file_exists($urltestFileName)) {
                    $return->addConfigurationFile($urltestFileName);
                }
            }
            $return->addTestDirectory($path, $recursive);
        } elseif (is_file($path) && basename($path) === 'urltest.yml') {
            $return->addConfigurationFile($path);
        } elseif (is_file($path)) {
            $return->addTestFile($path);
        } else {
            throw new \Exception('Invalid path or file name "' . $path . '".');
        }

        return $return;
    }
}
