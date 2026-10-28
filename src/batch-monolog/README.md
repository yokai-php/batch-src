# monolog/monolog bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-monolog?style=flat-square)](https://packagist.org/packages/yokai/batch-monolog)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-monolog?style=flat-square)](https://packagist.org/packages/yokai/batch-monolog)

[`monolog/monolog`](https://github.com/Seldaek/monolog) bridge for [Batch](https://github.com/yokai-php/batch) processing library.


# Installation

```
composer require yokai/batch-monolog
```


## Documentation

Please read the [dedicated documentation page](https://yokai-batch.readthedocs.io/en/1.x/bridges/monolog.html).

This package provides:

- an [job execution logger factory](https://github.com/yokai-php/batch-monolog/blob/1.x/src/StreamJobExecutionLoggerFactory.php) that create a local file logger
- an [job execution logger](https://github.com/yokai-php/batch-monolog/blob/1.x/src/StreamJobExecutionLogger.php) that write job logs to a local file


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
