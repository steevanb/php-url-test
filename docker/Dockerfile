FROM php:7.3-cli-alpine3.10

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/urltest

COPY . /var/urltest
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --classmap-authoritative

COPY docker/entrypoint.sh /var/entrypoint.sh
RUN chmod +x /var/entrypoint.sh

ENTRYPOINT ["/var/entrypoint.sh"]
