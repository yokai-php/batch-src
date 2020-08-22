# doctrine/orm bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-doctrine-orm?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-orm)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-doctrine-orm?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-orm)

Bridge of [`doctrine/orm`](https://github.com/doctrine/orm) for [Batch](https://github.com/yokai-php/batch).


## :warning: BETA

This library is following [semver](https://semver.org/).
However before we reach the first stable version (`v1.0.0`), we may decide to introduce **API changes in minor versions**.
This is why you should stick to a `v0.[minor].*` requirement !


# Installation

```
composer require yokai/batch-doctrine-orm
```


## Documentation

This package provides:

- an [item reader](docs/entity-item-reader.md) that read entities from entity manager


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
