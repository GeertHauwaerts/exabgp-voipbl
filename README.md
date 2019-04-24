## About

The ExaBGP process plugin script advertises the prefixes from a local and the
voipbl.org blacklist to ExaBGP via unicast or FlowSpec BGP.

[![Build Status](https://travis-ci.org/GeertHauwaerts/exabgp-voipbl.svg?branch=master)](https://travis-ci.org/GeertHauwaerts/exabgp-voipbl)

## Installation

The installation is very easy and straightforward:

  * Copy the `src/` directory to the location you would like to install it to.
  * Rename the `voipbl.conf.example` file to `voipbl.conf`.
  * Edit the configiration settings in `voipbl.conf`.
  * Test the installation by performing a cli-test via `php voipbl.php`.
  * Inject the script into ExaBGP.

> __Note:__
> The `voipbl.php` file works out-of-the-box without `composer` and has no external dependencies.

## ExaBGP API Compatibility

This application is compatible with the `ExaBGP 4.0 API` and automatically detects
when to use API acknowledgements.

## Library Usage

Instead of using the `voipbl.php` file, you can use the `ExaBGP\VoIPBL` classes independently. Check the
[ExaBGP\VoIPBL library documentation](docs/php/README.md) for the list of functions and their arguments.

```php
use ExaBGP\VoIPBL\Loader;

$exabgp = new Loader(__DIR__);
$exabgp->load();
```

```php
use ExaBGP\VoIPBL\Validator;

$validator = new Validator();

if ($validator->isIP('192.168.1.1')) {
  echo 'Valid IP';
}
```

```php
use ExaBGP\VoIPBL\Controller;

$controller = new Controller();
$controller->sendCommand(
  'announce route 192.168.1.1/32 next-hop 10.0.0.1'
);

$version = $controller->sendCommand('version', true);
```

## Development & Testing

To verify the integrity of the codebase you can run the PHP linter, unit tests, and update the library documentation:

```console
$ composer install
$ composer phpunit
$ composer phpcs
$ composer phpdoc
```

If a TTY is present, the application performs a dry-run mode, exiting after a single run. Without a TTY,
the application intercepts `ctrl+c` (interrupt signal) and runs continiously.

```console
$ php voipbl.php
```

This works fine to test a single run, but does not allow you to do any `STDOUT` manipulation. If you want to perform a dry-run with `STDOUT` support, use `unbuffer` to assign a pseudo TTY.

```console
$ unbuffer php voipbl.php >> output.txt
$ unbuffer php voipbl.php | wc -l
```

## Collaboration

The GitHub repository is used to keep track of all the bugs and feature
requests; I prefer to work uniquely via GitHib and Twitter.

If you have a patch to contribute:

  * Fork this repository on GitHub.
  * Create a feature branch for your set of patches.
  * Commit your changes to Git and push them to GitHub.
  * Submit a pull request.

Shout to [@GeertHauwaerts](https://twitter.com/GeertHauwaerts) on Twitter at
any time :)
