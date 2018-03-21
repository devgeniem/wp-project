# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [0.5.2] - 2018-03-21

### Added

- New Eslint rules: yoda (never), prefer-const (warn) and no-unused-vars (warn).

## [0.5.1] - 2018-03-19

### Removed

- The uploads redirect that is not needed anymore in `nginx/server/additions.conf`.

## [0.5.0] - 2018-03-19

### Added

- `devgeniem/wp-stateless-bucket-link-filter` mu-plugin to make image proxy work with WP Stateless.

## [0.4.0] - 2018-03-19

### Added

- Google Bucket environment variable templates into docker-compose files.

## [0.3.0] - 2018-03-19

### Added

- Nginx proxy cache settings for image caching.
- WP Stateless and its settings into `application.php`.
- DustPress, DustPress.js, DusPress Debugger.
- ACF Codifier.

### Removed

- Rspec tests. We use Codeception now.

## [0.2.0] - 2018-01-29

### Changed
- Moved back to the native mount instead of Docker-Sync.
## [0.1.2] - 2018-01-16

### Added
- Added an exception to the PHP CS rules so that CamelCased filenames are allowed.

## [0.1.1] - 2018-01-12

### Fixed

- Fixed the uploads dir path in `docker-sync.yml` configuration.

## [0.1.0] - 2017-12-13

### Changed

- Update tasks.cron's comment about WP cron.

### Fixed

- Remove the `PLL_LINGOTEK_AD` constant since it causes problems with the pro version of Polylang.

## [0.0.1] - 2017-11-20

### Added
- Added a PHP CS rule to allow dynamic indentation with rows that start with an object assignation arrow.

## [0.0.0] - 2017-10-26

### Added

- Added a default `WP_READONLY_OPTIONS` constant and set it to replace the `blog_public` option.

### Changed

- Tags now adhere to Semantic Versioning.

## [20171005] - 2017-10-05

### Added

- Add csscomb config for automatic style formatting.

### Changed

- Cross check the rules in sass-lint.yml and csscomb.json and modify the linting sort order to a better one.

## [20171004] - 2017-10-04

### Changed

- Fix the eslint config file to extend the proper standard and rename it according to current naming conventions of eslint.

## Unreleased

### Added

- Dockerfile and nginx -modifications to allow certificate varification by URL

## [20170928] - 2017-09-28

### Changed

- Changed all composer constraints to `^`.

### Added

- Require [WP Redis Group Cache](https://github.com/devgeniem/wp-redis-group-cache).
- This awesome changelog!

## [v20170112.0] - 2017-01-12

- Added `NGINX_CACHE_KEY` so that nginx http caching keys used for redis can be changed per project.

## [v20161220.0] - 2016-12-20

- Fix Dockerfile so that files like `web/favicon.ico` will be installed into the container.

## [v20161130.0] - 2016-11-30

- Changed envs `WP_UID` and `WP_GID` to `WEB_UID` and `WEB_GID`
- Added [devgeniem/wp-security-collection](https://github.com/devgeniem/wp-security-collection) package into composer.json

## [v20161123.0] - 20161123

- Added nginx templating with `*.tmpl` files. [Read related docs on *.tmpl templates](https://devgeniem.github.io/nginx/tmpl-templating/).
- Added environment specific nginx configurations with nginx/environments folder. [Read about custom includes in docs](https://devgeniem.github.io/nginx/custom-includes/).
- Added new version of webpack assets builder back to docker-compose.yml for automatic asset building
- Added Makefile for easy shortcuts
