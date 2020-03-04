# This is for production docker image with new relic php daemon
FROM devgeniem/ubuntu-docker-wordpress-newrelic:php74


# Use these uid/gid in production by default and change them when needed
ENV WEB_UID=10000 \
    WEB_GID=10001 \
    WEB_USER=wordpress \
    WEB_GROUP=web \
    # Change these in real environments
    BASIC_AUTH_USER=hello \
    BASIC_AUTH_PASSWORD_HASH='{PLAIN}world' \
    # New relic application name
    # Rename this for production
    NR_APP_NAME="WordPress Site"

# Skip dynamic user creation and
# create user with ID WEB_UID/WEB_GID here for nginx/php-fpm
# this saves some time in the startup in production
RUN addgroup --system --gid $WEB_GID $WEB_GROUP && \
    adduser --system --gid $WEB_GID --uid $WEB_UID $WEB_USER && \
    # Configure timezone for cron
    dpkg-reconfigure tzdata

##
# Install things in certain order to allow better caching
# Stuff that changes only rarely should be prioritized first
##

## Install wp core
COPY web/wp /var/www/project/web/wp
## Install all web root files with extension
COPY web/*.* /var/www/project/web/
## Copy certificate verification file
COPY web/.well-known /var/www/project/web/.well-known
## Install scripts
COPY scripts /var/www/project/scripts
## Install nginx configs
COPY nginx /var/www/project/nginx
## Install application config
COPY config/*.php /var/www/project/config/
## Install only the config that we want for the image and nothing else
COPY config/environments/staging.php /var/www/project/config/environments/
COPY config/environments/production.php /var/www/project/config/environments/
## Install vendor
COPY vendor /var/www/project/vendor
## Install cronjobs
COPY tasks.cron /var/www/project/
## Install wp-content
COPY web/app/*.php /var/www/project/web/app/
COPY web/app/languages /var/www/project/web/app/languages
COPY web/app/mu-plugins /var/www/project/web/app/mu-plugins
COPY web/app/plugins /var/www/project/web/app/plugins
COPY web/app/themes /var/www/project/web/app/themes

## To run cron with php-fpm we need to set permissions to cron runner script
RUN chmod +x /var/www/project/scripts/run-cron.sh
