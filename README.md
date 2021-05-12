![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)
# Geniem WordPress Project template.

[docker-wordpress-development]: https://github.com/devgeniem/ubuntu-docker-wordpress-development
[github-bedrock]: https://github.com/roots/bedrock
[github-gdev]: https://github.com/devgeniem/gdev
[github-phpcs]: https://github.com/squizlabs/PHP_CodeSniffer
[github-phpcs-rules]: https://github.com/devgeniem/geniem-rules-codesniffer
[github-phpstorm]: https://github.com/devgeniem/wp-project-phpstorm-settings

Use this as a local development environment with our docker-image
[devgeniem/ubuntu-docker-wordpress-development][docker-wordpress-development] and our development tools: [gdev][github-gdev].

## Features

- This resembles [roots/bedrock][github-bedrock] project layout.
- Uploads directory has been moved into /var/www/uploads (locally mapped into .docker/uploads)
- Uses composer for installing plugins
- Custom Nginx includes and env templating nginx configs

## Workflow for WP projects

1. After you have cloned this repository in the new client project replace all `THEMENAME` and
    `PROJECTNAME` references from all files from this project to your project name.
    * These can be for example: `ClientName` and `client-name`
2. Change project test address in `docker-compose.yml` for example `asiakas.test` -> `client-name.test`
3. Update `composer.json`
    * Rename the project `devgeniem/wp-project` -> `devgeniem/client`.
    * Add all people working in the project into `authors`
        * You can also add project managers, designers and other developers here.
        * This is important so that we always have accountable people to advise with the project later on when it eventually might turn to more legacy project.
4. Use included linters for the code style and best practises
    * We use [PHP_CodeSniffer][github-phpcs] with custom config in `phpcs.xml`
      which contains [Geniem Coding Standards][github-phpcs-rules].
    * This ruleset is here to help and make the developer to think about possible vulnerabilities.
6. Update this Readme as many times as you can.
    * Most important details are usually the details about data models, and their input/output.
    * Also add all 3rd-party dependencies here
7. Replace `BASIC_AUTH_USER` and `BASIC_AUTH_PASSWORD_HASH` from `Dockerfile` with real credentials.
    * You can find more info about formats here: http://nginx.org/en/docs/http/ngx_http_auth_basic_module.html
    * For example, you can generate password hash with: `$ openssl passwd -crypt "password"`

## IDE Support

We have [preconfigured PhpStorm settings available][github-phpstorm].
These will be automatically installed when you run `$ make init`.

## Start local development

This project includes example `docker-compose.yml` which you can use to develop locally. Ideally you would use [gdev][github-gdev].

Probably the easiest way to start is to run:

```
$ make init
```

This starts the local development environment, installs packages using composer, builds project assets and seeds the database.

## Debugging and profiling

### Xdebug
For debugging and profiling we use **Xdebug 3**. To enable Xdebug features, change its mode in compose file's environment variable `XDEBUG_MODE`.
```
XDEBUG_MODE: off
```
For different mode values see: https://xdebug.org/docs/all_settings#mode. Once enabled, remote debugging uses port `9003` by default.

You can also define the IDE session key in `${XDEBUG_IDE_KEY}`. By default it is a generic value: `DEBUG`. Set it in your browser extension and editor configurations.
```
XDEBUG_IDE_KEY: DEBUG
```

For all environment variables for Xdebug, see the base image's [Dockerfile](https://github.com/devgeniem/ubuntu-docker-wordpress-development/blob/master/ubuntu-php-7.4/Dockerfile)

## Testing

You can run the php codesniffer, rspec and sitespeed tests by using the Makefile:
```
$ make test
```

Open the url you provided in step 2 for example: `client-name.test` and start developing the site.

## Google cloud build

The project base provides templates to build/test/deploy the project via Google Cloud Build (GCB) to Kontena.
The Google cloud CI/CD configuration files are under `gcloud/`.
There are separate config files to configure staging and production environments.
(This assumes it's a Geniem project. For other projects you need to also replace all the secrets as documented by Google)

To enable a build pipeline, do the following:

1. Replace `PROJECTNAME` and `THEMENAME` in the yaml files.
2. Replace mentions of `asiakas` in the yaml files
    - including `tests/acceptance.suite.yml` and the Kontena files.
3. Uncomment webpack/phpcs/integration test steps as needed
   - Integration tests is still work in progress
   - Configure them in `tests/` if enabling
4. Create build triggers to GCB
   - Trigger from push to branch or tag in GitHub
   - Build configuration type: `cloudbuild.yaml`.
   - Set location as `gcloud/cloudbuild_stage.yaml` or `gcloud/cloudbuild_production.yaml`
5. Run the build once to store image in gcr.io
6. Install Kontena stack.

See `gcloud/README.md` for more.

## Changelog

[CHANGELOG.md](/CHANGELOG.md)

## Environment variables

The project uses environment variables to define settings for WordPress.
*This is not a complete list!*

### `WP_BLOG_PUBLIC`

This environment variable controls the WordPress `blog_public` [option](https://codex.wordpress.org/Option_Reference#Privacy) via the [WP Readonly Options](https://github.com/devgeniem/wp-readonly-options) plugin.

**Values**

- `1` *(integer) (default)* I would like my blog to be visible to everyone, including search engines.
- `0` *(integer)* I would like to block search engines, but allow normal visitors.

## Composer dependencies' descriptions

### Used repositories

| Repository             | Description                         |
|------------------------|-------------------------------------|
| wpackagist.org         | The main repo for WordPress plugins |
| wp-languages.github.io | Koodimonni's repo for keeping WP language packets up-to-date through composer |

### Used plugins / vendor packages

Please see actual minimum version requirements from `composer.json` file.

| Packages                       | Description    |
|--------------------------------|----------------|
| `johnpbloch/wordpress`         | WordPress Core |
| `vlucas/phpdotenv`             | Loads environment variables from .env to `getenv()` to be used in project configs. |
| `oscarotero/env`               | Provides `env()` helper function. |
| `composer/installers`          | We're able to specify different paths for packages with this. WP plugins, for example, are installed to web/app/plugins with the help of this package. |
| `koodimonni/composer-dropin-installer` |  We use this to be able to install Koodimonni's language packets via composer. With this installer we can install multiple packets to one folder. We can also install our object-cache-dropin to its needed path with this. |
| `koodimonni-language/core-fi`  | Finnish language for WordPress via Composer |
| `devgeniem/better-wp-db-error` | Adds a prettier database connection error page. |
| `wpackagist-plugin/stream`     | This is used to monitor actions made by different users of the WP admin. |
| `devgeniem/wp-redis-object-cache-dropin` | The geniem redis object cache dropin package. |
| `rarst/wps`                    | Whoops debugging for WordPress |

### List of plugin and dropin paths

Type mapping value, `type:wordpress-plugin` for example,
[refers to composer.json project type](https://getcomposer.org/doc/04-schema.md#type).

If you want to override install path, just add the package (like `rarst/wps` below) to
type mapping array of your selection.

| Installer paths               | Type mapping                                 |
|-------------------------------|----------------------------------------------|
| `web/wp`                      | The WordPress Core: `wordpress-install-dir`  |
| `web/app/mu-plugins/{$name}/` | `["type:wordpress-muplugin", "rarst/wps"]`   |
| `web/app/plugins/{$name}/`    | `["type:wordpress-plugin"]`                  |
| `web/app/themes/{$name}`      | `["type:wordpress-theme"]`                   |

#### Custom paths for Koodimonni's dropin-installer

| Dropin path                  | Type mapping                            |
|------------------------------|-----------------------------------------|
| `web/app/`                   | `["type:wordpress-dropin"]`             |
| `web/app/languages/`         | `["vendor:koodimonni-language"]`        |
| `web/app/languages/plugins/` | `["vendor:koodimonni-plugin-language"]` |
| `web/app/languages/themes/`  | `["vendor:koodimonni-theme-language"]`  |
