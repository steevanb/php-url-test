#!/usr/bin/env sh

set -e

readonly PROJECT_DIR=$(realpath $(dirname $(realpath $0))/..)

docker build $PROJECT_DIR --file=docker/Dockerfile -t steevanb/php-url-test:0.0.15
docker logout
docker login --username=steevanb
docker push steevanb/php-url-test:0.0.15
