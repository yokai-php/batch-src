# league/flysystem bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-league-flysystem?style=flat-square)](https://packagist.org/packages/yokai/batch-league-flysystem)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-league-flysystem?style=flat-square)](https://packagist.org/packages/yokai/batch-league-flysystem)

Bridge of [`league/flysystem`](https://github.com/thephpleague/flysystem) for [Batch](https://github.com/yokai-php/batch).


## :warning: BETA

This library is following [semver](https://semver.org/).
However before we reach the first stable version (`v1.0.0`), we may decide to introduce **API changes in minor versions**.
This is why you should stick to a `v0.[minor].*` requirement !


# Installation

```
composer require yokai/batch-league-flysystem
```


## Documentation

This package provides:

- a [job](docs/copy-files-job.md) that copy file(s) from one filesystem to another
- a [job](docs/move-files-job.md) that move file(s) from one filesystem to another


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
