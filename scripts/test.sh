#!/bin/bash
set -e

# Get script directory
DIR=$(dirname $(readlink -f "$0"))

##
# Run rspec in tests folder through phantomjs
##
rspec $DIR/../tests/rspec/test.rb