---
layout: page
title: "Basic auth"
category: nginx
date: 2016-11-17 16:02:12
order: 5
---

> Before enabling Basic auth for your site you need to understand [how nginx includes work in wp-project]({% post_url 2016-11-29-custom-includes %}).

Basic auth is used for restricting access to your site. This is useful for public testing/staging environments. This way only you and the client can access the page.

## For example: Basic auth for staging environment

Add `nginx/environments/staging/basic-auth.conf.tmpl` with:

```
##
# Use password for staging environment
##
auth_basic           "Restricted environment";
auth_basic_user_file ${__DIR__}/.htpasswd;
```

Generate new password hash using openssl:

```bash
$ openssl passwd -crypt "password"
WjwOpfuB2pppo
```

And add username and password hash into `nginx/environments/staging/.htpasswd` like:

```
user:WjwOpfuB2pppo
```

You might need to set the envs with flynn:

```
flynn -c stage -a PROJECT_NAME env set BASIC_AUTH_USER=user BASIC_AUTH_PASSWORD_HASH=WjwOpfuB2pppo
```

## Links
More info about [ngx_http_auth_basic_module in nginx docs](http://nginx.org/en/docs/http/ngx_http_auth_basic_module.html).
