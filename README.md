# Twelve-Factor WordPress

WordPress, the [Twelve-Factor](http://12factor.net/) way: fully managed using Composer and configured using environment variables.

## Quick Deploy

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

Afterwards, in Heroku's Dashboard under "Settings" for your deployed application, remove the `WORDPRESS_ADMIN_*` environment variables.

To set up WordPress' Cron Jobs using [Heroku Scheduler](https://elements.heroku.com/addons/scheduler), see further below.

## Manual Deploy

### Clone

```
$ git clone https://github.com/dzuelke/wordpress-12factor
$ cd wordpress-12factor
$ composer install # optional
```

### Create Application and Add-Ons

```
$ heroku create
$ heroku addons:create jawsdb
$ heroku addons:create bucketeer
$ heroku addons:create sendgrid
```

### Set WordPress Keys and Salts

```
$ heroku config:set $(curl 'https://api.wordpress.org/secret-key/1.1/salt/' | sed -E -e "s/^define\('(.+)', *'(.+)'\);$/WORDPRESS_\1=\2/" -e 's/ //g')
```

### Deploy

```
$ git push heroku master
```

### Finalize Installation

```
$ heroku run 'composer wordpress-setup-core-install -- --title="WordPress on Heroku" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --url="http://example.herokuapp.com/"'
$ heroku run 'composer wordpress-setup-finalize'
```

As an alternative to the first step, if you'd like to interactively provide info (use a format like "`http://example.herokuapp.com`" with your app name for the URL), you can run:

```
$ heroku run 'vendor/bin/wp core install --prompt'
```

### Run

```
$ heroku open
```

## Updating WordPress and Plugins

```
$ composer update
$ git add composer.json composer.lock
$ git commit -m "new WordPress and Plugins"
$ git push heroku master
```

## WordPress Cron

1. Run `heroku config:set DISABLE_WP_CRON=true` to disable built-in cron jobs
1. Add [Heroku Scheduler](https://elements.heroku.com/addons/scheduler) to your application
1. Add a job that, every 30 minutes, runs `vendor/bin/wp cron event run --all`