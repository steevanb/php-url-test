[![version](https://img.shields.io/badge/alpha-0.3.3-red.svg)](https://github.com/steevanb/php-url-test/tree/0.3.3)
![Lines](https://img.shields.io/badge/code%20lines-4479-green.svg)
![Total Downloads](https://poser.pugx.org/steevanb/php-url-test/downloads)

## php-url-test

Tests all urls of your application

![Url test](example.jpg)

[Changelog](changelog.md)

## Installation

/!\ Keep in mind this is an alpha version /!\

Don't allow to update minor/bug fix versions, as we can break compatibility between bug fixes until final release.

```bash
composer require --dev steevanb/php-url-test 0.3.*
```

## Use it with official Docker image

Instead of install it in your project with Composer, you can use official Docker image.

```bash
docker run \
    # Create a volume with your test configurations into /app
    -v /var/www/tests:/app \
    # You can use `URLTEST_PARAMETERS` env variable to add parameters to `urltest` command.
    -e URLTEST_PARAMETERS="--ansi --configuration=/app/urltest.yml -vvv" \
    # Allow this container to access host domains
    --net=host \
    steevanb/php-url-test:0.3.3
```

## Launch tests

```bash
# scan tests/ to find *.urltest.yml files, --recursive=false or -r=false to not do it recursively
# if urltest.yml file is found into tests/ (not in sub directories), it will be used for default configuration file
vendor/bin/urltest tests/

# test url_test_foo
vendor/bin/urltest tests/ url_test_foo

# test url_test_foo and all tests who match preg pattern /^url_test_bar[0..9]{1,}$/
vendor/bin/urltest tests/ url_test_foo,/^url_test_bar[0..9]{1,}$/

# launch tests from foo.urltest.yml only
vendor/bin/urltest tests/Tests/foo.urltest.yml

# don't use tests/urltest.yml, use another configuration file
# if you are a few developers with different domain for each developer,
# you can create a configuration file by developer and use parameters to configure it
vendor/bin/urltest tests/ --configuration=tests/foo.yml
```
## Read test results and show informations

```bash
# show only failed test comparison (by default), use -v, -vv or -vvv to get more informations
vendor/bin/urltest tests/ --reader=steevanb\\PhpUrlTest\\ResultReader\\ConsoleResultReader#error

# show only passed test comparison, use -v, -vv or -vvv to get more informations
vendor/bin/urltest tests/ --reader=steevanb\\PhpUrlTest\\ResultReader\\ConsoleResultReader#success
```

You can create your own ResultReader, by implementing _steevanb\PhpUrlTest\ResultReader\ResultReaderInterface_.

Then you can use it as you use ConsoleResultReader, with `--reader` parameter.

You can separate readers by `,`:
```bash
vendor/bin/urltest tests/ --reader=steevanb\\PhpUrlTest\\ResultReader\\ConsoleResultReader#error,Foo\\Bar#success,Foo\\Baz
```

## Stop on error and resume your tests

You have 3 parameters to stop tests when a test fail, and resume tests from the one who fail, or skip it and continue after this one :

```bash
# stop when a test fail
vendor/bin/urltest tests/ --stop-on-error

# when a test fail, continue testing since the one who fail (do not re-test previous ones)
vendor/bin/urltest tests/ --stop-on-error --continue

# used with --continue, skip last fail test, and continue testing after this one (do not re-test previous ones)
vendor/bin/urltest tests/ --skip
```

## Change directory where UrlTest could write files

```bash
vendor/bin/urltest tests/ --var-path=/foo
```

## Dump configuration

```bash
# dump only global configuration
vendor/bin/urltest --dump-configuration tests/

# dump global configuration, and url_test_foo configuration
vendor/bin/urltest --dump-configuration tests/ url_test_foo

# dump global configuration, url_test_foo configuration and all configurations who id match preg pattern /^url_test_bar[0..9]{1,}$/
vendor/bin/urltest --dump-configuration tests/ url_test_foo,/^url_test_bar[0..9]{1,}$/
```

## YAML test file example

Only _request.url_ is required.

```yaml
testId:
    # If abstract = true, this test will not be launched. you can use it as default configuration with parent: testId in another test
    abstract: false
    # Id of parent default configuration
    parent: ~
    # 0 is first. don't use negative numbers, it's used by UrlTest
    position: 0
    events:
        # Commands called before the test. it could be a string (for only one command) or an array of commands.
        beforeTest:
            - command
        # Commands called after the test. it could be a string (for only one command) or an array of commands.
        afterTest:
            - commands
    request:
        # You can use parameters (see above) to configure what you need
        url: '%domain%/foo'
        timeout: 30
        port: 80
        method: GET
        userAgent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36
        referer: http://referer.com
        allowRedirect: true
        # List of headers to add to request
        headers:
            X-Foo: bar
    expectedResponse:
        url: http://test.dev
        code: 200
        size: 300
        contentType: text/html
        numConnects: 1
        # Set count if you know exaclty number of redirects you want to test, or min/max
        redirect:
            min: 1
            max: 1
            count: 1
        header:
            size: 200
            # List of headers who has to exists, and have exaclty this value
            headers:
                X-Foo: bar
            # List of headers should not exists
            unallowedHeaders:
                - X-Bar
        body:
            # Content to compare with response, <file($fileName)> will get content of $fileName
            content: <file('content.html')>
            size: 100
            # Transformer id : transform data from content key before comparing it to response
            transformer: json
            # File name where tranformed expected content will be saved, if you need to test your transformer for example
            fileName: /tmp/urlTestResult/expectedResponse.html
    response:
        body:
            # Transformer id : transform data from response body before comparing it to expected response
            transformer: json
            # File name where response body will be saved
            fileName: /tmp/urlTestResult/response.html
```

You can define default configurations for all tests in your _.urltest.yml_ file :
```yaml
_defaults:
    # here you can define sames configurations as for a test
    # this configurations will be applied to all tests in this file, if value is not defined, null or ~
```

## ResponseBodyTransformer

A response body transformer will modify response body at 2 differents steps:
 * `expectedResponse.body.transformer`: transform expected response
 * `response.body.transformer`: transform response

List of available transformers:
 * `json`: try to decode and reencode value, will transform response to null if data is not a valid JSON
 * `uuid`: try to decode response (should be a valid JSON) and replace all UUID value by `____UUID____`

## Create your own ResponseBodyTransformer

To create your own ResponseBodyTransformer, you have to do this steps:
 * Create a class who implement `steevanb\PhpUrlTest\ResponseBodyTransformer\ResponseBodyTransformerInterface`
 * Register your ResponseBodyTransformer with `UrlTest::addResponseBodyTransformer()`

## Global configuration file

You can define global configurations in _urltest.yml_.

This configurations will be applied to all tests.

```yaml
# you can define tests here, or abstract tests to use it in all your tests
urltest:
    abstractTestId:
        abstract: true
        url: http://test.dev

# parameters can be used in almost all urltest configurations
# define it's value here, and use it with %parameterName% in your configuration
parameters:
    domain: 'http://foo.local'
```
