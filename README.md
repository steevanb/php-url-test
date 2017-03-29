[![unstable](https://img.shields.io/badge/unstable-master-red.svg)](https://github.com/steevanb/php-url-test)

php-url-test
============

Tests all urls of your application

YAML test file example
======================

Only request.url is required.

```yaml
testId:
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
