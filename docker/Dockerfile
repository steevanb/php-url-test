FROM php:7.3-cli-alpine3.10

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/urltest

RUN \
    echo '{"require": {"steevanb/php-url-test": "0.0.14"}}' > composer.json \
    && composer update --no-dev --classmap-authoritative

COPY entrypoint.sh /var/entrypoint.sh
RUN chmod +x /var/entrypoint.sh

ENTRYPOINT ["/var/entrypoint.sh"]
