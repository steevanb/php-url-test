FROM php:7.4.3-cli-alpine3.11

RUN \
    apk update \
    && apk add bash

COPY . /var/urltest

COPY docker/entrypoint /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /app
ENTRYPOINT ["entrypoint"]
