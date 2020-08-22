# box/spout bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-box-spout?style=flat-square)](https://packagist.org/packages/yokai/batch-box-spout)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-box-spout?style=flat-square)](https://packagist.org/packages/yokai/batch-box-spout)

[`box/spout`](https://github.com/box/spout) bridge for [Batch](https://github.com/yokai-php/batch) processing library.


## :warning: BETA

This library is following [semver](https://semver.org/).
However before we reach the first stable version (`v1.0.0`), we may decide to introduce **API changes in minor versions**.
This is why you should stick to a `v0.[minor].*` requirement !


# Installation

```
composer require yokai/batch-box-spout
```


## Documentation

This package provides:

- a [item reader](docs/flat-file-item-reader.md) that read from CSV/XLSX/ODS files
- a [item writer](docs/flat-file-item-writer.md) that write to CSV/XLSX/ODS files


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
