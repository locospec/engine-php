# This is my package engine-php

[![Latest Version on Packagist](https://img.shields.io/packagist/v/locospec/engine-php.svg?style=flat-square)](https://packagist.org/packages/locospec/engine-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/locospec/engine-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/locospec/engine-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/locospec/engine-php.svg?style=flat-square)](https://packagist.org/packages/locospec/engine-php)

This is where your description should go. Try and limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/engine-php.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/engine-php)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require locospec/engine-php
```

## Usage

```php
$skeleton = new Locospec\LCS();
echo $skeleton->echoPhrase('Hello, Locospec!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities

## Credits

-   [Rajiv Seelam](https://github.com/rjvim)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

npx commitlint --from v1.0.0-alpha.4 --to HEAD --verbose

conventional-changelog -i CHANGELOG.md -s -r 0

conventional-changelog -i CHANGELOG.md -s

# Generate for a specific version range

conventional-changelog -p angular -i CHANGELOG.md -s -r 0 --commit-path . --from v1.0.0-alpha.4 --to HEAD

conventional-changelog -i CHANGELOG.md -s --commit-path . --from v1.0.0-alpha.4 --to HEAD

conventional-changelog -i CHANGELOG.md -s -r 0

conventional-changelog -n ./.changelog.config.js -i CHANGELOG.md -s --commit-path . --from v1.0.0-alpha.4 --to HEAD

conventional-changelog -n ./.changelog.config.js -i CHANGELOG.md -s -r 0
