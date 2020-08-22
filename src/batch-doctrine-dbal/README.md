# doctrine/dbal bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-doctrine-dbal?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-dbal)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-doctrine-dbal?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-dbal)

[`doctrine/dbal`](https://github.com/doctrine/dbal) bridge for [Batch](https://github.com/yokai-php/batch) processing library.


## :warning: BETA

This library is following [semver](https://semver.org/).
However before we reach the first stable version (`v1.0.0`), we may decide to introduce **API changes in minor versions**.
This is why you should stick to a `v0.[minor].*` requirement !


# Installation

```
composer require yokai/batch-doctrine-dbal
```


## Documentation

This package provides:

- a [job execution storage](docs/job-execution-storage.md) that stores job executions to a relational database


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
