---
layout: page
title: ".tmpl templating"
category: nginx
date: 2016-11-29 13:05:18
order: 2
---

Nginx doesn't support environmental variables easily in the configs. To use envs in nginx configuration files our docker container has custom [startup script](https://github.com/devgeniem/docker-wordpress/blob/master/debian-php7.0/rootfs/etc/cont-init.d/00-render-templates) which renders all `${ENV}` blocks from `*.tpml` files inside `nginx` folder.

Templating engine uses `envsubst` command and replaces the result in filename without `.tmpl` extension.

## For example

Add `nginx/server/redis-header.conf.tmpl` file inside your project like this:

```
# Add X-I-Have-Redis header when system uses redis
set $redis_host '${REDIS_HOST}';
if ( $redis_host != '' ) {
    add_header X-I-Have-Redis '${REDIS_HOST}';
}
```

When container starts and has `REDIS_HOST=10.254.254.254` it will render that file into `nginx/server/redis-header.conf` containing:

```
# Add X-I-Have-Redis header when system uses redis
set $redis_host '10.254.254.254';
if ( $redis_host != '' ) {
    add_header X-I-Have-Redis '10.254.254.254';
}
```

## Supported magic variables
`${__DIR__}` will have the absolute path to directory where the file is located.

## Supported envs
This is list of env which can be used in `*.tmpl` files.

```
PORT
WEB_ROOT
WEB_USER
WEB_GROUP
NGINX_ACCESS_LOG
NGINX_ERROR_LOG
NGINX_ERROR_LEVEL
NGINX_INCLUDE_DIR
NGINX_MAX_BODY_SIZE
NGINX_FASTCGI_TIMEOUT
WP_ENV
REDIS_HOST
REDIS_PORT
REDIS_DATABASE
REDIS_PASSWORD
NGINX_REDIS_CACHE_TTL_MAX
NGINX_REDIS_CACHE_TTL_DEFAULT
NGINX_REDIS_CACHE_PREFIX
BASIC_AUTH_USER
BASIC_AUTH_PASSWORD_HASH
```
