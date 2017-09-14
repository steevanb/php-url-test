[![version](https://img.shields.io/badge/alpha-0.0.8-red.svg)](https://github.com/steevanb/php-url-test/tree/0.0.8)
![Lines](https://img.shields.io/badge/code%20lines-3106-green.svg)
![Total Downloads](https://poser.pugx.org/steevanb/php-url-test/downloads)

php-url-test
============

Tests all urls of your application

![Url test](example.jpg)

[Changelog](changelog.md)

### Installation

/!\ Keep in mind this is an alpha version /!\

```bash
composer require --dev "steevanb/php-url-test": "^0.0.8"
```

### Launch tests

```bash
# scan src/ to find *.urltest.yml files, --recursive=true or -r=true to do it recursively
./bin/urltest src/

# test url_test_foo
./bin/urltest src/ url_test_foo

# test url_test_foo and all tests who match preg pattern /^url_test_bar[0..9]{1,}$/
./bin/urltest src/ url_test_foo,/^url_test_bar[0..9]{1,}$/

# launch tests from foo.urltest.yml only
./bin/urltest src/Tests/foo.urltest.yml
```

If _path_ (_src/_ in example) is a directory, urltest will try to find _urltest.yml_ global configuration file in this directory.

You can specify a global configuration file in _path_, example : _src/myurltest.yml_

### Show success or fail informations

```bash
# show test comparison (success + fail), use -v, -vv or -vvv to get more informations
--comparator=console

# show only fail test comparison (by default), use -v, -vv or -vvv to get more informations
--errorcomparator=console
```

### Stop on error and resume your tests

You have 3 parameters to stop tests when a test fail, and resume tests from the one who fail, or skip it and continue after this one :

```bash
# stop when a test fail
--stop-on-error

# when a test fail, continue testing since the one who fail (do not re-test previous ones)
--continue

# used with --continue, skip last fail test, and continue testing after this one (do not re-test previous ones)
--skip
```

### Dump configuration

```bash
# dump only global configuration
./bin/urltest --dump-configuration src/

# dump global configuration, and url_test_foo configuration
./bin/urltest --dump-configuration src/ url_test_foo

# dump global configuration, url_test_foo configuration and all configurations who id match preg pattern /^url_test_bar[0..9]{1,}$/
./bin/urltest --dump-configuration src/ url_test_foo,/^url_test_bar[0..9]{1,}$/
```

### YAML test file example

Only _request.url_ is required.

```yaml
testId:
    # if abstract = true, this test will not be launched. you can use it as default configuration with parent: testId in another test
    abstract: false
    # id of parent default configuration
    parent: ~
    # 0 is first. don't use negative numbers, it's used by UrlTest
    position: 0
    request:
        url: http://test.dev
        timeout: 30
        port: 80
        method: GET
        userAgent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36
        referer: http://referer.com
        allowRedirect: true
        # list of headers to add to request
        headers:
            X-Foo: bar
    expectedResponse:
        url: http://test.dev
        code: 200
        size: 300
        contentType: text/html
        numConnects: 1
        # set count if you know exaclty number of redirects you want to test, or min/max
        redirect:
            min: 1
            max: 1
            count: 1
        header:
            size: 200
            # list of headers who has to exists, and have exaclty this value
            headers:
                X-Foo: bar
            # list of headers should not exists
            unallowedHeaders:
                - X-Bar
        body:
            # content to compare with response, <file($fileName)> will get content of $fileName
            content: <file('content.html')>
            size: 100
            # transformer id : transform data from content key before comparing it to response
            transformer: json
            # file name where tranformed expected content will be saved, if you need to test your transformer for example
            fileName: /tmp/urlTestResult/expectedResponse.html
    response:
        body:
            # transformer id : transform data from response body before comparing it to expected response
            transformer: json
            # file name where response body will be saved
            fileName: /tmp/urlTestResult/response.html
```

You can define default configurations for all tests in your _.urltest.yml_ file :
```yaml
_defaults:
    # here you can define sames configurations as for a test
    # this configurations will be applied to all tests in this file, if value is not defined, null or ~
```

### Global configuration file

You can define global configurations in _urltest.yml_.

This configurations will be applied to all tests.

```yaml
# you can define tests here, or abstract tests to use it in all your tests
urltest:
    abstractTestId:
        abstract: true
        url: http://test.dev
```
