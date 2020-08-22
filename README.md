# Yokai Batch Development Monorepo

[![Tests](https://img.shields.io/github/workflow/status/yokai-php/batch-src/Tests?style=flat-square&label=tests)](https://github.com/yokai-php/batch-src/actions)
[![Coverage](https://img.shields.io/codecov/c/github/yokai-php/batch-src?style=flat-square)](https://codecov.io/gh/yokai-php/batch-src)
[![Contributors](https://img.shields.io/github/contributors/yokai-php/batch-src?style=flat-square)](https://github.com/yokai-php/batch-src/graphs/contributors)

This repository contains sources for all packages from `yokai/batch` suite.


## Packages

- [**Main repository**](https://github.com/yokai-php/batch): Core classes/interfaces of batch architecture
- [**Box Spout bridge**](https://github.com/yokai-php/batch-box-spout): Read/Write from/to CSV/ODS/XLSX
- [**Doctrine DBAL bridge**](https://github.com/yokai-php/batch-doctrine-dbal): Store job executions in relational database
- [**Doctrine ORM bridge**](https://github.com/yokai-php/batch-doctrine-orm): Read from Doctrine ORM entities
- [**Doctrine persistence bridge**](https://github.com/yokai-php/batch-doctrine-persistence): Write to Doctrine ORM entities
- [**Symfony console bridge**](https://github.com/yokai-php/batch-symfony-console): Add command to trigger jobs and async job launcher via command
- [**Symfony framework bridge**](https://github.com/yokai-php/batch-symfony-framework): Bundle to integrate with Symfony framework
- [**Symfony messenger bridge**](https://github.com/yokai-php/batch-symfony-messenger): Trigger jobs using message dispatch
- [**Symfony serializer bridge**](https://github.com/yokai-php/batch-symfony-serializer): Process items using (de)normalization, serialize job execution for certain storages
- [**Symfony validator bridge**](https://github.com/yokai-php/batch-symfony-validator): Skip invalid items during process


## Contribution

Please feel free to open an [issue](https://github.com/yokai-php/batch-src/issues)
or a [pull request](https://github.com/yokai-php/batch-src/pulls)
in the [main repository](https://github.com/yokai-php/batch-src).

The library was originally created by [Yann Eugoné](https://github.com/yann-eugone).
See the list of [contributors](https://github.com/yokai-php/batch-src/contributors).


## License

This library is under MIT [LICENSE](LICENSE).
