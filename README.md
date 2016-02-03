# Twelve-Factor WordPress

WordPress, the [Twelve-Factor](http://12factor.net/) way: fully managed using Composer and configured using environment variables.

## General Concepts and Considerations

The WordPress installation is fully contained in a `wordpress` subfolder upon `composer install`. A `wp-config.php` resides in the root of the project, and uses several different environment variables to control behavior.

Automatic updates for WordPress or plugins, and theme editing, are disabled intentionally. What you deploy is what gets executed, which makes setups simple to deploy, and, most importantly, reproducible. See further below for information on how to update WordPress versions.

The configuration file is kept as generic as possible; on Heroku, add-ons [JawsDB](https://elements.heroku.com/addons/jawsdb) (for MySQL), [Bucketeer](https://elements.heroku.com/addons/bucketeer) (for S3 storage), and [SendGrid](https://elements.heroku.com/addons/sendgrid) (for E-Mails) are used.

The assumption is that this installation runs behind a load balancer whose `X-Forwarded-Proto` header value can be trusted; it is used to determine whether the request protocol is HTTPS or not.

HTTPS is forced for Login and Admin functions. `WP_DEBUG` is on; errors do not get displayed, but should get logged to PHP's default error log, accessible e.g. using `heroku logs`.

## Quick Deploy

If you have a [Heroku](http://heroku.com) account, you may simply use the following button to deploy this application:

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

After the deploy, in [Heroku's Dashboard](https://dasboard.heroku.com) under "Settings" for your deployed application, **remove the `WORDPRESS_ADMIN_*` environment variables**.

To set up WordPress' Cron Jobs using [Heroku Scheduler](https://elements.heroku.com/addons/scheduler), see further below.

## Manual Deploy

### Clone

Clone this repo:

```
$ git clone https://github.com/dzuelke/wordpress-12factor
$ cd wordpress-12factor
```

If you like, you can locally install dependencies with [Composer](https://getcomposer.org):

```
$ composer install
```

### Create Application and Add-Ons

Create a new app and add add-ons for MySQL, S3 and E-Mail:

```
$ heroku create
$ heroku addons:create jawsdb
$ heroku addons:create bucketeer
$ heroku addons:create sendgrid
```

### Set WordPress Keys and Salts

This will use the WordPress secret keys service, parse out the values, and set them as [config vars](https://devcenter.heroku.com/articles/config-vars):

```
$ heroku config:set $(curl 'https://api.wordpress.org/secret-key/1.1/salt/' | sed -E -e "s/^define\('(.+)', *'(.+)'\);$/WORDPRESS_\1=\2/" -e 's/ //g')
```

You can also generate your own key and set all required variables yourself (see section further below).

### Deploy

```
$ git push heroku master
```

### Finalize Installation

This will create tables and set up an admin user:

```
$ heroku run 'composer wordpress-setup-core-install -- --title="WordPress on Heroku" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --url="http://example.herokuapp.com/"'
```

If you'd like to interactively provide info instead (use a format like "`http://example.herokuapp.com`" with your app name for the URL), you can run:

```
$ heroku run 'vendor/bin/wp core install --prompt'
```

Finally, the following command will configure and enable plugins and set a reasonable structure for Permalinks:

```
$ heroku run 'composer wordpress-setup-finalize'
```

### Visit Blog

Navigate to the application's URL, or open your browser the lazy way:

```
$ heroku open
```

## Updating WordPress and Plugins

To update all dependencies:

```
$ composer update
```

Alternatively, run `composer update johnpbloch/wordpress` to only update WordPress, or e.g. `composer update wpackagist-plugin/sendgrid-email-delivery-simplified` to only update that plugin.

Afterwards, add, commit and push the changes:

```
$ git add composer.json composer.lock
$ git commit -m "new WordPress and Plugins"
$ git push heroku master
```

## WordPress Cron

Instead of having WordPress check on each page load if Cron Jobs need to be run (thus potentially slowing down the site for some users), you can invoke Cron externally:

1. Run `heroku config:set DISABLE_WP_CRON=true` (or set it using the [Heroku Dashboard](https://dasboard.heroku.com)) to disable built-in cron jobs;
1. Add [Heroku Scheduler](https://elements.heroku.com/addons/scheduler) to your application;
1. Add a job that, every 30 minutes, runs `vendor/bin/wp cron event run --all`.

## Environment Variables

`wp-config.php` will use the following environment variables (if multiple are listed, in order of precedence):

### Database Connection

`DATABASE_URL` or `JAWSDB_URL` or `CLEARDB_DATABASE_URL` (format `mysql://user:pass@host:port/dbname`) for database connections.

### AWS/S3

* `AWS_ACCESS_KEY_ID` or `BUCKETEER_AWS_ACCESS_KEY_ID` for the AWS Access Key ID;
* `AWS_SECRET_ACCESS_KEY` `BUCKETEER_AWS_SECRET_ACCESS_KEY` for the AWS Secret Access Key;
* `S3_BUCKET` or `BUCKETEER_BUCKET_NAME` for the name of the S3 bucket;
* `S3_REGION` for a non-default S3 region name.

### SendGrid

`SENDGRID_USERNAME` and `SENDGRID_PASSWORD` for SendGrind credentials.

### WordPress Secrets

`WORDPRESS_AUTH_KEY`, `WORDPRESS_SECURE_AUTH_KEY`, `WORDPRESS_LOGGED_IN_KEY`, `WORDPRESS_NONCE_KEY`, `WORDPRESS_AUTH_SALT`, `WORDPRESS_SECURE_AUTH_SALT`, `WORDPRESS_LOGGED_IN_SALT`, `WORDPRESS_NONCE_SALT` should contain random secret keys for various WordPress functions; values can be obtained from https://api.wordpress.org/secret-key/1.1/salt/ (also see Manual Deploy instructions further above).

### Miscellaneous

`DISABLE_WP_CRON` set to "1" or "true" will disable automatic cron execution through browsers (see further above).
