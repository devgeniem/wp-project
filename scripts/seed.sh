#!/usr/bin/env bash

##
# This file is used to create basic starting point content for dev/test/stage/production
##

# Set defaults
export MYSQL_HOST=${DB_PORT_3306_TCP_ADDR-$MYSQL_HOST}
export MYSQL_PORT=${MYSQL_PORT-$DB_PORT}

# Wait until mysql is open
nc -z $MYSQL_HOST $MYSQL_PORT
if [[ $? != 0 ]] ; then
  echo "Waiting mysql to open in $DB_HOST:$DB_PORT..."
  declare -i i
  while ! nc -z $MYSQL_HOST $MYSQL_PORT; do
    if [ "$i" == "15" ]; then
      echo "Error: Mysql process timeout"
      exit 1
    fi
    i+=1
    sleep 1
  done
fi

# Fail after errors
set -e


# Get script directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "[INFO]: Starting to import database seed..."


##
# Set default values for WP
##
export WP_ADMIN_USER=${WP_ADMIN_USER-admin}
# Generate password if it's missing
if [ "$WP_ADMIN_PASSWORD" == "" ]; then
    export WP_ADMIN_PASSWORD=$(openssl rand -base64 18)
fi

if [ "$SERVER_NAME" != "" ]; then
    export WP_ADMIN_EMAIL=admin@$SERVER_NAME
elif [ "$SMTP_FROM" != "" ]; then
    export WP_ADMIN_EMAIL=$SMTP_FROM
else
    export WP_ADMIN_EMAIL=admin@asiakas.test
fi

export WP_SITEURL=${WP_SITEURL-http://$SERVER_NAME}
export WP_TITLE=${WP_TITLE-WordPress}

if [ -f "seed.sql" ]; then
    echo "Seeding database from seed.sql"
    mysql -u$MYSQL_USER -p$MYSQL_PWD -h$MYSQL_HOST $MYSQL_DATABASE < seed.sql
else
    # Install WordPress if not installed yet
    if wp core version > /dev/null && ! wp core is-installed; then

        if wp site is-installed 2>&1 | grep 'Site' | grep 'not found' > /dev/null; then
            echo "[INFO]: This is Multisite installation"

            # Install Multisite
            wp core multisite-install --title=$WP_TITLE --admin_password=$WP_ADMIN_PASSWORD --admin_email=$WP_ADMIN_EMAIL

            # Activate all plugins for all sites
            wp plugin activate --all --network

            echo "[INFO]: WordPress Multisite: $WP_TITLE is now installed..."
        else
            # Install basic tables
            wp core install --url=$WP_SITEURL --title=$WP_TITLE \
                            --admin_user=$WP_ADMIN_USER --admin_email=$WP_ADMIN_EMAIL \
                            --admin_password=$WP_ADMIN_PASSWORD

            # Activate all plugins
            wp plugin activate --all

            echo "[INFO]: WordPress: $WP_TITLE is now installed..."
        fi

        echo "[INFO]: You can login to WordPress with credentials: $WP_ADMIN_USER / $WP_ADMIN_PASSWORD"
    fi

    # Install pages and navigation
    echo "Continuing database seed with data/seed.php"
    wp eval-file $(dirname $DIR)/data/seed.php --skip-plugins=stream

    # Generate some dummy posts so that pagination on rss will work properly
    wp post generate --count=11
fi
