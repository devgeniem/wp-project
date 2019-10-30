#!/bin/bash

# If BASIC_AUTH_PASSWORD is set use basic authentication on curl.
if [[ "${BASIC_AUTH_USER}" ]] && [[ "${BASIC_AUTH_PASSWORD}" ]]; then
curl -u $BASIC_AUTH_USER:$BASIC_AUTH_PASSWORD $CRON_URL > /dev/null 2>&1
elif [[ "${BASIC_AUTH_USER}" ]] && [[ "${BASIC_AUTH_PASSWORD_HASH}" ]]; then
curl -u $BASIC_AUTH_USER:${BASIC_AUTH_PASSWORD_HASH/\{PLAIN\}/} $CRON_URL > /dev/null 2>&1
else
curl $CRON_URL > /dev/null 2>&1
fi