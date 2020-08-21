<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PACKAGE_DIRECTORIES, ['src']);
    $parameters->set(Option::DIRECTORIES_TO_REPOSITORIES, [
        'src/*' => 'git@github.com:yokai-php/*.git',
    ]);
    $parameters->set(Option::DATA_TO_APPEND, [
        'authors' => [
            [
                'name' => 'Yann Eugoné',
                'email' => 'eugone.yann@gmail.com',
            ],
        ],
    ]);
};
