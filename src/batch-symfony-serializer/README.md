# symfony/serializer bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-symfony-serializer?style=flat-square)](https://packagist.org/packages/yokai/batch-symfony-serializer)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-symfony-serializer?style=flat-square)](https://packagist.org/packages/yokai/batch-symfony-serializer)

Bridge of [`symfony/serializer`](https://github.com/symfony/serializer) for [Batch](https://github.com/yokai-php/batch).


# Installation

```
composer require yokai/batch-symfony-serializer
```


## Documentation

Please read the [dedicated documentation page](https://yokai-batch.readthedocs.io/en/latest/bridges/symfony-serializer.html).

This package provides:

- an [item job processor](https://github.com/yokai-php/batch-symfony-serializer/blob/1.x/src/NormalizeItemProcessor.php) that normalizes every item
- an [item job processor](https://github.com/yokai-php/batch-symfony-serializer/blob/1.x/src/DenormalizeItemProcessor.php) that denormalizes every item


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
