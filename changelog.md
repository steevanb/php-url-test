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
