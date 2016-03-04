#!/bin/bash
set -e

##
# This file is used to create:
# - development database in dev
# - import production database in testing environment (TODO)
# - import migration database if this is new production
##

# Get script directory
DIR=$(dirname $(readlink -f "$0"))

echo "Starting to import database seed..."

if wp core is-installed; then
  echo "Database already exists, skipping seed..."
  exit 0
elif ! wp core version; then
  echo "ERROR: Couldn't find wp-core. Aborting..."
  exit 1
fi

##
# Add seed or necessary changes to database
##
if [ "$WP_ENV" = "production" ]; then

  echo "Importing seed data from db/wordpress.sql..."
  wp db import $DIR/../db/wordpress.sql

elif [ "$WP_ENV" = "development" ]; then

  echo "Importing seed data from db/wordpress.sql..."
  wp db import $DIR/../db/wordpress.sql

elif [ "$WP_ENV" = "testing" ]; then
  
  echo "Importing seed data from production database..."
  mysqldump -h $PRODUCTION_HOST -u$PRODUCTION_USER -p$PRODUCTION_PASSWORD $PRODUCTION_DB $DIR/../db/dump.sql

  wp db import $DIR/../db/dump.sql
fi