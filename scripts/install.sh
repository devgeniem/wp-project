#!/bin/bash
set -e

# Get script directory
DIR=$(dirname $(readlink -f "$0"))

# Install composer dependencies
composer install --working-dir=$DIR/../