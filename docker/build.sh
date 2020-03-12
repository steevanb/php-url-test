#!/usr/bin/env bash

set -e

readonly PROJECT_DIR=$(realpath $(dirname $(realpath $0))/..)
readonly URLTEST_VERSION="0.1.1"

docker run --rm --interactive --tty --volume ${PROJECT_DIR}:/app --user $(id -u):$(id -g) composer install --no-dev --classmap-authoritative
docker build $PROJECT_DIR --file=${PROJECT_DIR}/docker/Dockerfile -t steevanb/php-url-test:${URLTEST_VERSION}
docker run --rm --interactive --tty --volume ${PROJECT_DIR}:/app --user $(id -u):$(id -g) composer install

if [ "$1" == "--push" ]; then
    docker logout
    docker login --username=steevanb
    docker push steevanb/php-url-test:${URLTEST_VERSION}
fi
