# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Optimized Google Cloud Build configurations to run things concurrently when possible.

## [0.11.0] - 2020-01-22

### Changed

- Allow insecure connection when using curl for the cron url.
- Change file permissions to allow execution for the run-cron.sh.

### Added
- Correct error log path variables for PHP and NGINX for local environments and the Google Cloud build.

## [0.10.0] - 2019-11-19

### Changed
- Updated WordPress minimum version to 5.3
- Updated Composer Installer minimum version to 1.7
- Updated DustPress.js minimum version to 4.0

## [0.9.6] - 2019-11-11

### Added
- 2Gb memorylimit for redis and web container for staging and production

## [0.9.5] - 2019-10-29

### Changed
- Updated Redis Group Cache to use version 3.x.

## [0.9.4] - 2019-10-17

### Added
- nginx fullpage cache headers for staging 60 sec and stale and erro 120 sec

## [0.9.3] - 2019-10-03

### Changed
- Cloud build fixes where `npm run build` is run instead of `./node_modules/webpack/bin/webpack.js`
- Change cron url from `tasks.cron` to match the correct path in `scripts`.
- Add `web/core`to gitignore.
- Add lines to sass-lint.
- Change color shorthand rule from csscomb.json.
- Change proper IP addresses to `docker-compose-ubuntu.yml`.
- Change SEO collection version to ^2.0 in `composer.json`.
- Add phpcs ignores to `db-config.php` and `db.php`.
- Fix code style errors from `application.php`.
- changed errorlog path for local

## [0.9.2] - 2019-09-24
- changed production image to use tag php73 and development to use php73xd2 (only build that is working with xdebug currently)

## [0.9.1] - 2019-05-20

### Changed

- Change eslint configuration to extend the new [@wordpress/eslint-plugin](https://www.npmjs.com/package/@wordpress/eslint-plugin) instead of the deprecated WordPress configuration package.

### Fixed
- Missing space in kontena-production.yml

## [0.9.0] - 2019-05-20

### Added
- Database dump file extensions to .dockerignore to prevent them from bloating the images.
- Add `devgeniem/wp-disable-redis-object-cache-dropin` to disable Redis object caching on WP preview.

### Changed
- Disabled basic auth for uploads directory even if it was on for the rest of the project.

### Fixed
- Make the deny rule for author urls to be more exact to prevent mismatching locations.
- Removed duplicate config from kontena-stage.yml

## [0.8.2] - 2019-03-14

### Changed
- cron to use PHP-FPM with run-cron.sh script

## [0.8.1] - 2018-11-18

### Added
- An example of how to allow a single endpoint of the wp rest api

### Fixed
- Fixed a problem where you could access /wp-json but not /wp-json/

## [0.8.0] - 2018-11-12

### Added

- `WP_REDIS_MAXTTL` constant set to 30 days for production environment and to 4 hours for staging environment.

### Changed

- `WP_REDIS_MAXTTL` constant set to 60 seconds for development environment.

## [0.7.0] - 2018-10-29

### Changed
- Xdebug profiling data is stored in a global volume and not synced to the local machine.

## [0.6.4] - 2018-09-28
- Add WP_BLOG_PUBLIC:0 to configuration files

## [0.6.3] - 2018-06-04
- Removed Flynn stuff from Dockerfile
- Added cron runner priviledges to Dockerfile
- Added optional beta image commented out to Dockerfile
- Added kontena ymls
- Added index folders to nginx folder to enable project spesific config to index block eg. basic auth
- Deleted skip_cache.conf
- Added skip_cache.conf back to original location to prevent crash
- Added skipcache folder and skip-cache.conf
- Added Kontena related configs
- Removed composer lock
- Added redirect templates
- Added run cron
- Added nginx helper
- Added google api key to docker-compose

## [0.6.3] - 2018-10-22
- Changed run-cron.sh to use basic authentication if envs BASIC_AUTH_USER and BASIC_AUTH_PASSWORD has been setted.

## [0.6.2] - 2018-06-04
- Changed pagespeed config: InPlaceResourceOptimization on; -> off

## [0.6.1] - 2018-04-25

### Added

- Add hashes by default to filenames so we can upload files with the same name

### Changed

- cors headers
- removed 5 min browser cache

## [0.6.0] - 2018-04-02

### Changed

- Change the default revision amount to 5.

### Fixed

- Use `filter_var` on accessing server variables instead of `filter_input` to support PHP7.x.

## [0.5.4] - 2018-03-23

### Fixed

- Filename corrected for `nginx/server/skip_cache.conf`.

## [0.5.3] - 2018-03-21

### Added
- Eslint rule to consider js files modules

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
