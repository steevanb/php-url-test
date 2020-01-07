FROM php:7.3-cli-alpine3.10

WORKDIR /var/urltest

COPY . /var/urltest

COPY docker/entrypoint.sh /var/entrypoint.sh
RUN chmod +x /var/entrypoint.sh

ENTRYPOINT ["/var/entrypoint.sh"]
