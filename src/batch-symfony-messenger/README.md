# symfony/messenger bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-symfony-messenger?style=flat-square)](https://packagist.org/packages/yokai/batch-symfony-messenger)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-symfony-messenger?style=flat-square)](https://packagist.org/packages/yokai/batch-symfony-messenger)

[`symfony/messenger`](https://github.com/symfony/messenger) bridge for [Batch](https://github.com/yokai-php/batch) processing library.


## :warning: BETA

This library is following [semver](https://semver.org/).
However before we reach the first stable version (`v1.0.0`), we may decide to introduce **API changes in minor versions**.
This is why you should stick to a `v0.[minor].*` requirement !


# Installation

```
composer require yokai/batch-symfony-messenger
```


## Documentation

This package provides:

- a [job launcher](docs/job-launcher.md) that uses messages to launch jobs


## Contribution

This package is a readonly split of a [larger repository](https://github.com/yokai-php/batch-src),
containing all tests and sources for all librairies of the batch universe.

Please feel free to open an [issue](https://github.com/yokai-php/batch-src/issues)
or a [pull request](https://github.com/yokai-php/batch-src/pulls)
in the [main repository](https://github.com/yokai-php/batch-src).

The library was originally created by [Yann Eugoné](https://github.com/yann-eugone).
See the list of [contributors](https://github.com/yokai-php/batch-src/contributors).


## License

This library is under MIT [LICENSE](LICENSE).
