#!/bin/bash

# IF THIS FILE IS NOT BEING EXECUTED VIA CRON:
# THERE MIGHT BE A WINDOWS LINE ENDING IN THE FILE AND MUST BE CONVERTED TO UNIX LINE ENDINGS.

# If BASIC_AUTH_PASSWORD is set use basic authentication on curl.
if [[ "${BASIC_AUTH_USER}" ]] && [[ "${BASIC_AUTH_PASSWORD}" ]]; then
curl -u $BASIC_AUTH_USER:$BASIC_AUTH_PASSWORD $CRON_URL --insecure > /dev/null 2>&1
elif [[ "${BASIC_AUTH_USER}" ]] && [[ "${BASIC_AUTH_PASSWORD}" ]]; then
curl -u $BASIC_AUTH_USER:${BASIC_AUTH_PASSWORD/\{PLAIN\}/} $CRON_URL --insecure > /dev/null 2>&1
else
curl $CRON_URL --insecure > /dev/null 2>&1
fi
