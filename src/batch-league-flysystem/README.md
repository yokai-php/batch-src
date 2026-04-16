# league/flysystem bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-league-flysystem?style=flat-square)](https://packagist.org/packages/yokai/batch-league-flysystem)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-league-flysystem?style=flat-square)](https://packagist.org/packages/yokai/batch-league-flysystem)

Bridge of [`league/flysystem`](https://github.com/thephpleague/flysystem) for [Batch](https://github.com/yokai-php/batch).


# Installation

```
composer require yokai/batch-league-flysystem
```


## Documentation

Please read the [dedicated documentation page](https://yokai-batch.readthedocs.io/en/latest/bridges/league-flysystem.html).

This package provides:

- a [job](https://github.com/yokai-php/batch-league-flysystem/blob/1.x/src/Job/CopyFilesJob.php) that copy file(s) from one filesystem to another
- a [job](https://github.com/yokai-php/batch-league-flysystem/blob/1.x/src/Job/MoveFilesJob.php) that move file(s) from one filesystem to another
- a [scheduler](https://github.com/yokai-php/batch-league-flysystem/blob/1.x/src/Scheduler/FileFoundScheduler.php) that triggers job when file is found on a filesystem


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
