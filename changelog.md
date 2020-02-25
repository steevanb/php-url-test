### [0.1.0](../../compare/0.0.17...0.1.0) - 2020-02-25

- Add `src/` subdirectory for source code.
- `docker/build.sh` use docker for Composer.

### [0.0.17](../../compare/0.0.16...0.0.17) - 2020-01-21

- Replace parameters where it was missing:
  * request.postData
  * request.headers
  * expectedResponse.header.headers
  * expectedResponse.header.unallowedHeaders
  * expectedResponse.body.content

### [0.0.16](../../compare/0.0.15...0.0.16) - 2020-01-07

- Add `uuid` ResponseBodyTransformer.

### [0.0.15](../../compare/0.0.14...0.0.15) - 2019-11-26

- Fix when more than 1 parameter is configured in `urltest.yml`.
- Console output: don't show `Headers:` result when no headers.
- Console output: write `NULL` for values when is null instead of empty string.
- Console output: write HTTP query error message when possible.
- Console output: write port in url.
- Add Docker image.

### [0.0.14](../../compare/0.0.13...0.0.14) - 2018-09-27

- Fix `_defaults.parent` configuration was not taken into account
- Fix PHP error when no tests found in `urltest:test` Command

### [0.0.13](../../compare/0.0.12...0.0.13) - 2018-09-26

- Replace ResponseComparator by ResultReader
- Replace `--comparator` and `--errorcomparator` by `--reader` in _urltest:test_

### [0.0.12](../../compare/0.0.11...0.0.12) - 2018-09-25

- Move _CreateUrlTestServiceTrait_ from _steevanb\PhpUrlTest\Command_ to _steevanb\PhpUrlTest_
- Remove _CreateUrlTestServiceTrait::createFilteredIdsUrlTestService()_
- Add `--configuration` to _urltest:configuration:dump_ and _urltest:test_ to indicate your configuration file 
- Add `$configurationFileName` parameter to _CreateUrlTestServiceTrait::createUrlTestService()_
- UrlTest ids now have same syntax between _urltest:configuration:dump_ and _urltest:test_
- New UrlTest will now have `response.code: 200` default value
- Add parameters to configuration file

### [0.0.10](../../compare/0.0.9...0.0.10) - 2017-10-04

- Fix response header parsing

### [0.0.9](../../compare/0.0.8...0.0.9) - 2017-10-03

- Add _YamlExporter_, to export UrlTest into yaml
- Add _urltest:configuration:export_ command, to export a test into yaml
- Add _position_ configuration to test, to choose it's position in test chain
- Add _--export-configuration_ parameter to _bin/urltest_, to export configuration into yaml 
- Working on parallel tests, do not use it for now

### [0.0.8](../../compare/0.0.7...0.0.8) - 2017-09-11

- [Feature #2](https://github.com/steevanb/php-url-test/projects/2) Create global configuration file _urltest.yml_
- [Feature #10](https://github.com/steevanb/php-url-test/projects/10) Create abstract tests
- [Bugfix #1](https://github.com/steevanb/php-url-test/issues/1) Fix _defaults required value should not be

### [0.0.7](../../compare/0.0.6...0.0.7) - 2017-09-07

- [Bugfix #3](https://github.com/steevanb/php-url-test/issues/3) Rename _default to _defaults in _.urltest.yml_ files
- [Bugfix #4](https://github.com/steevanb/php-url-test/issues/4) Fix _--stop-on-error_ alert shown when only one test passed
- [Feature #5](https://github.com/steevanb/php-url-test/issues/5) Remove _.php_ from _bin/urltest.php_
- [Project #5](https://github.com/steevanb/php-url-test/projects/5) Add verbose mode to --dump-configuration, to show tests list

### [0.0.6](../../compare/0.0.5...0.0.6) - 2017-07-03

- Trow exception when body transformer class not found
- Sort tests by id on UrlTestService::addTest()

### [0.0.5](../../compare/0.0.4...0.0.5) - 2017-05-03

- Add UrlTestService::addSkippedTest(), UrlTestService::getSkippedTests(), UrlTestService::isSkippedTest() and UrlTestService::countSkippedTests()
- Add UrlTestService::getFailedTests() and UrlTestService::countFailTests()
- Add UrlTestService::getSuccessTests() and UrlTestService::countSuccessTests()
- Add UrlTestService::isAllTestsExecuted()
- Add UrlTestService::setContinue()
- Add --stop-on-error, --continue and --skip to bin/urltest.php
- Add skipped tests in yellow to bin/urltest.php when --progress=true (by default)
- Add UrlTest::isExecuted() and UrlTest::setValid()
- ResponseComparatorService will now compare only executed tests
- Fix bin/urltest.php default autoload.php path

### [0.0.4](../../compare/0.0.3...0.0.4) - 2017-05-01

- Add --dump-configuration to bin/urltest.php

### [0.0.3](../../compare/0.0.2...0.0.3) - 2017-04-18

- Console error comparator by default
- Add $ids parameters, to test this ids
- Add filename to exception when yaml parsing fail

### [0.0.2](../../compare/0.0.1...0.0.2) - 2017-04-14

- Add filename and test id in exception
- Add request.postData
- Write <empty> when body is empty

### [0.0.1](../../compare/0.0.0...0.0.1) - 2017-04-13

- Add _default key in urltest.yml, to define default configuration
- Fix header comparison

### 0.0.0 - 2017-04-11

- Create first alpha
