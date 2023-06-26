# Yokai Batch Development Monorepo

[![Tests](https://img.shields.io/github/actions/workflow/status/yokai-php/batch-src/tests.yml?branch=0.x&style=flat-square&label=tests)](https://github.com/yokai-php/batch-src/actions)
[![Coverage](https://img.shields.io/codecov/c/github/yokai-php/batch-src?style=flat-square)](https://codecov.io/gh/yokai-php/batch-src)
[![Contributors](https://img.shields.io/github/contributors/yokai-php/batch-src?style=flat-square)](https://github.com/yokai-php/batch-src/graphs/contributors)

This repository contains sources for all packages from `yokai/batch` suite.


## Packages

The core repository [`yokai/batch`](https://github.com/yokai-php/batch) : contains classes/interfaces of batch architecture.

Some bridges to popular packages :

| Bridge with                                                                        |                                                                |
|------------------------------------------------------------------------------------|----------------------------------------------------------------|
| `DEPRECATED` [`box/spout`](https://github.com/yokai-php/batch-box-spout)           | Read/Write from/to CSV/ODS/XLSX                                |
| [`doctrine/dbal`](https://github.com/yokai-php/batch-doctrine-dbal)                | Read/Write from/to SQL databases                               |
| [`doctrine/orm`](https://github.com/yokai-php/batch-doctrine-orm)                  | Read from Doctrine ORM entities                                |
| [`doctrine/persistence`](https://github.com/yokai-php/batch-doctrine-persistence)  | Write to Doctrine ORM/ODM objects                              |
| [`league/flysystem`](https://github.com/yokai-php/batch-league-flysystem)          | Copy/Move files in a job / Trigger job when file found         |
| [`openspout/openspout`](https://github.com/yokai-php/batch-openspout)              | Read/Write from/to CSV/ODS/XLSX                                |
| [`symfony/console`](https://github.com/yokai-php/batch-symfony-console)            | Add command to trigger jobs and async job launcher via command |
| [`symfony/framework-bundle`](https://github.com/yokai-php/batch-symfony-framework) | Bundle to integrate with Symfony framework                     |
| [`symfony/messenger`](https://github.com/yokai-php/batch-symfony-messenger)        | Trigger jobs using message dispatch                            |
| [`symfony/serializer`](https://github.com/yokai-php/batch-symfony-serializer)      | Process items using (de)normalization                          |
| [`symfony/validator`](https://github.com/yokai-php/batch-symfony-validator)        | Skip invalid items during process                              |

And some special packages :
- [`yokai/batch-symfony-pack`](https://github.com/yokai-php/batch-symfony-pack): Minimal pack for Symfony Framework


## Documentation

Every package has its own documentation,
you should start with [core repository documentation](https://github.com/yokai-php/batch/blob/0.x/README.md).


## Contribution

Please feel free to open an [issue](https://github.com/yokai-php/batch-src/issues)
or a [pull request](https://github.com/yokai-php/batch-src/pulls)
in the [main repository](https://github.com/yokai-php/batch-src).

The library was originally created by [Yann Eugoné](https://github.com/yann-eugone).
See the list of [contributors](https://github.com/yokai-php/batch-src/contributors).


## License

This library is under MIT [LICENSE](LICENSE).
