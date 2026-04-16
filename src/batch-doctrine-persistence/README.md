# doctrine/persistence bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-doctrine-persistence?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-persistence)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-doctrine-persistence?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-persistence)

Bridge of [`doctrine/persistence`](https://github.com/doctrine/persistence) for [Batch](https://github.com/yokai-php/batch).


# Installation

```
composer require yokai/batch-doctrine-persistence
```


## Documentation

Please read the [dedicated documentation page](https://yokai-batch.readthedocs.io/en/latest/bridges/doctrine-persistence.html).

This package provides:

- an [object registry](https://github.com/yokai-php/batch-doctrine-persistence/blob/1.x/src/ObjectRegistry.php) that remembers found objects identities
- an [item writer](https://github.com/yokai-php/batch-doctrine-persistence/blob/1.x/src/ObjectWriter.php) that persists objects through object manager


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
