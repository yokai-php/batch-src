# Yokai Batch Development Monorepo

[![Tests](https://img.shields.io/github/workflow/status/yokai-php/batch-src/Tests?style=flat-square&label=tests)](https://github.com/yokai-php/batch-src/actions)
[![Coverage](https://img.shields.io/codecov/c/github/yokai-php/batch-src?style=flat-square)](https://codecov.io/gh/yokai-php/batch-src)
[![Contributors](https://img.shields.io/github/contributors/yokai-php/batch-src?style=flat-square)](https://github.com/yokai-php/batch-src/graphs/contributors)

This repository contains sources for all packages from `yokai/batch` suite.


## Packages

The core repository [`yokai/batch`](https://github.com/yokai-php/batch) : contains classes/interfaces of batch architecture.


Some bridges to popular packages :

| Bridge package                                                            | Batch package                                                                                 | Description                                                       |
| ------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- |
| [`box/spout`](https://github.com/box/spout)                               | [`yokai/batch-box-spout`](https://github.com/yokai-php/batch-box-spout)                       | Read/Write from/to CSV/ODS/XLSX                                   |
| [`doctrine/dbal`](https://github.com/doctrine/dbal)                       | [`yokai/batch-doctrine-dbal`](https://github.com/yokai-php/batch-doctrine-dbal)               | Store job executions in relational database                       |
| [`doctrine/orm`](https://github.com/doctrine/orm)                         | [`yokai/batch-doctrine-orm`](https://github.com/yokai-php/batch-doctrine-orm)                 | Read from Doctrine ORM entities                                   |
| [`doctrine/persistence`](https://github.com/doctrine/persistence)         | [`yokai/batch-doctrine-persistence`](https://github.com/yokai-php/batch-doctrine-persistence) | Write to Doctrine ORM/ODM objects                                 |
| [`symfony/console`](https://github.com/symfony/console)                   | [`yokai/batch-symfony-console`](https://github.com/yokai-php/batch-symfony-console)           | Add command to trigger jobs and async job launcher via command    |
| [`symfony/framework-bundle`](https://github.com/symfony/framework-bundle) | [`yokai/batch-symfony-framework`](https://github.com/yokai-php/batch-symfony-framework)       | Bundle to integrate with Symfony framework                        |
| [`symfony/messenger`](https://github.com/symfony/messenger)               | [`yokai/batch-symfony-messenger`](https://github.com/yokai-php/batch-symfony-messenger)       | Trigger jobs using message dispatch                               |
| [`symfony/serializer`](https://github.com/symfony/serializer)             | [`yokai/batch-symfony-serializer`](https://github.com/yokai-php/batch-symfony-serializer)     | Process items using (de)normalization                             |
| [`symfony/validator`](https://github.com/symfony/validator)               | [`yokai/batch-symfony-validator`](https://github.com/yokai-php/batch-symfony-validator)       | Skip invalid items during process                                 |


And some special packages :
- [`yokai/batch-symfony-pack`](https://github.com/yokai-php/batch-symfony-pack): Minimal pack for Symfony Framework


## Contribution

Please feel free to open an [issue](https://github.com/yokai-php/batch-src/issues)
or a [pull request](https://github.com/yokai-php/batch-src/pulls)
in the [main repository](https://github.com/yokai-php/batch-src).

The library was originally created by [Yann Eugoné](https://github.com/yann-eugone).
See the list of [contributors](https://github.com/yokai-php/batch-src/contributors).


## License

This library is under MIT [LICENSE](LICENSE).
