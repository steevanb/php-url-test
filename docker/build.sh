#!/usr/bin/env sh

set -e

readonly PROJECT_DIR=$(realpath $(dirname $(realpath $0))/..)

composer install --no-dev --classmap-authoritative
docker build $PROJECT_DIR --file=docker/Dockerfile -t steevanb/php-url-test:0.0.17
composer install
docker logout
docker login --username=steevanb
docker push steevanb/php-url-test:0.0.17
