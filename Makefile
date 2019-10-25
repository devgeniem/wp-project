##
# Nothing beats the original Makefile for simplicity
##

# Load few settings in variables
CONTAINER_ID = $(shell docker-compose ps -q web)
SERVER_NAME = $(shell docker exec $(CONTAINER_ID) printenv SERVER_NAME)
DNS_SERVER = 10.254.254.254
PHP_FILES = $(shell git ls-files | grep .php)

# This alias runs all comands
init: ide-support start install build seed
	##
	# You can now start developing the site in $(SERVER_NAME)
	##
all: init test

# Install default config templates for PhpStorm
# These will be ignored by git
ide-support:
	# Installing basic settings for PhpStorm in .idea ...
	git clone https://github.com/devgeniem/wp-project-phpstorm-settings .idea
	rm -rf .idea/.git

start:
	# Starting the development environment...
	gdev up
install:
	# Installing dependencies with composer
	composer install --ignore-platform-reqs
build:
	# Building frontend assets with docker & webpack...
	docker-compose run --rm webpack-builder
seed:
	# Installing database seed
	docker exec -it $(CONTAINER_ID) ./scripts/seed.sh
clean:
	# Cleaning up development environment...
	docker exec -it $(CONTAINER_ID) wp-cli db export development.sql --allow-root --path=web/wp
	gdev stop
	gdev rm -f
test:
	# Running php codesniffer tests in docker...
	docker-compose run --rm style-test phpcs --runtime-set ignore_warnings_on_exit true --standard=phpcs.xml $(PHP_FILES)
	# Displaying sitespeed.io best practise / performance / accessibility results...
	docker run --rm --privileged --dns $(DNS_SERVER) sitespeedio/coach https://$(SERVER_NAME) -b chrome --details --description
	# Running sitespeed.io performance budget tests in docker...
	docker run --rm --privileged --dns $(DNS_SERVER) -v $(shell pwd)/tests:/sitespeed.io sitespeedio/sitespeed.io --budget sitespeed-budget.json -b chrome -n 2 https://$(SERVER_NAME)

