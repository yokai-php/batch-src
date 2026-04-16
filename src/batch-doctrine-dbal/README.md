# doctrine/dbal bridge for Batch processing library

[![Latest Stable Version](https://img.shields.io/packagist/v/yokai/batch-doctrine-dbal?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-dbal)
[![Downloads Monthly](https://img.shields.io/packagist/dm/yokai/batch-doctrine-dbal?style=flat-square)](https://packagist.org/packages/yokai/batch-doctrine-dbal)

[`doctrine/dbal`](https://github.com/doctrine/dbal) bridge for [Batch](https://github.com/yokai-php/batch) processing library.


# Installation

```
composer require yokai/batch-doctrine-dbal
```


## Documentation

Please read the [dedicated documentation page](https://yokai-batch.readthedocs.io/en/1.x/bridges/doctrine-dbal.html).

This package provides:

- an [item job writer](https://github.com/yokai-php/batch-doctrine-dbal/blob/1.x/src/DoctrineDBALInsertWriter.php) that insert into an SQL table
- a [job execution storage](https://github.com/yokai-php/batch-doctrine-dbal/blob/1.x/src/DoctrineDBALJobExecutionStorage.php) that stores job executions to a relational database
- an [item job reader](https://github.com/yokai-php/batch-doctrine-dbal/blob/1.x/src/DoctrineDBALQueryCursorReader.php) that read from an SQL table using cursor pagination
- an [item job reader](https://github.com/yokai-php/batch-doctrine-dbal/blob/1.x/src/DoctrineDBALQueryOffsetReader.php) that read from an SQL table using limit + offset pagination
- an [item job writer](https://github.com/yokai-php/batch-doctrine-dbal/blob/1.x/src/DoctrineDBALUpsertWriter.php) that insert or update into an SQL table


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
