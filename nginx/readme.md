# Nginx includes in wp-project
## Server
All files matching 'server/*.conf' will be included to server block {} in nginx in docker container.

## Http
All files matching 'http/*.conf' will be included to http block {} in nginx in docker container.
For example you can use this to include [ngx_http_geoip_module](http://nginx.org/en/docs/http/ngx_http_geoip_module.html) directives to filter out ip addresses allowed in wp-admin.

## Environment based configs
You can provide custom nginx configs which are used in matching environments. For example `development`, `staging` and `production` are included.

## Templating with *.tmpl format
All files ending with `.tmpl` will have their embedded enviromental variables rendered.

It means that containers startup scripts will replace all instances of `${ENV}` in all `*.tmpl` files under this folder.

### Supported envs in templates
Currently it supports these env:
```
$PORT
$WEB_ROOT
$WEB_USER
$WEB_GROUP
$NGINX_ACCESS_LOG
$NGINX_ERROR_LOG
$NGINX_ERROR_LEVEL
$NGINX_INCLUDE_DIR
$NGINX_MAX_BODY_SIZE
$NGINX_FASTCGI_TIMEOUT
$WP_ENV
$REDIS_HOST
$REDIS_PORT
$REDIS_DATABASE
$REDIS_PASSWORD
$NGINX_REDIS_CACHE_TTL_MAX
$NGINX_FASTCGI_CACHE_VALID
$NGINX_REDIS_CACHE_PREFIX
```

And a magic variable `$__DIR__` which will have the absolute path of the directory of your `tmpl` file.

### For example:
```
##
# Use password for this environment
##
auth_basic           "${WP_ENV} environment";
auth_basic_user_file ${__DIR__}/.htpasswd;
```
