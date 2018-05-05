Installation
===

There are two ways of installing this system, Composer and Phar. The Phar is probably
the most convenient, but either will work just as well.

Composer
---

This [repo is on Packagist](https://packagist.org/packages/halfer/gitlab-ls-registry), so
something like this should be fine:

    {
        "require": {
            "halfer/gitlab-ls-registry": "v0.1.*"
        }
    }

Then do `composer install` or `composer update` in the usual way.

To make use of it, load the autoloader:

    require_once __DIR__ . '/vendor/autoload.php';

Phar
---

Just pull the required binary from the [builds](builds) folder, like so:

    wget https://github.com/halfer/gitlab-ls-registry/blob/master/builds/gitlab-ls-registry-0.1.1.phar?raw=true

And then you can require it like so:

    require_once __DIR__ . '/gitlab-ls-registry-0.1.1.phar';
