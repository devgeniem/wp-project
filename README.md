# Geniem Wordpress Project template.

Use this with our docker-image: [devgeniem/alpine-wordpress](https://github.com/devgeniem/docker-alpine-wordpress).

## Features
- This resembles [roots/bedrock](https://github.com/roots/bedrock) project layout.
- Uploads directory has been moved into /data/uploads (locally mapped into .docker/uploads)
- Uses composer for installing plugins

## Start local development
This project includes example `docker-compose.yml` which you can use to develop locally.

```
$ docker-compose up -d
```