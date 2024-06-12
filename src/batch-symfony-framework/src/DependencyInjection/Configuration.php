<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for yokai/batch Symfony Bundle.
 *
 * @phpstan-type Config array{
 *      storage: StorageConfig,
 *      launcher: LauncherConfig,
 *      parameters: ParametersConfig,
 *      ui: UserInterfaceConfig,
 *  }
 * @phpstan-type StorageConfig array{
 *      service?: string,
 *      dbal?: array{
 *          connection: string,
 *          table: string,
 *      },
 *      filesystem: array{
 *          serializer: string,
 *          dir: string,
 *      },
 *  }
 * @phpstan-type LauncherConfig array{
 *      default: string|null,
 *      launchers: array<string, string>,
 *  }
 * @phpstan-type ParametersConfig array{
 *      global: array<string, mixed>,
 *      per_job: array<string, array<string, mixed>>,
 *  }
 * @phpstan-type UserInterfaceConfig array{
 *      enabled: bool,
 *      security: array{
 *          attributes: array{
 *              list: string,
 *              view: string,
 *              traces: string,
 *              logs: string,
 *          },
 *      },
 *      templating: array{
 *          prefix: string|null,
 *          service: string|null,
 *          base_template: string|null,
 *      },
 *  }
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        /** @var ArrayNodeDefinition $root */
        $root = ($treeBuilder = new TreeBuilder('yokai_batch'))->getRootNode();

        $root
            ->children()
                ->append($this->storage())
                ->append($this->launcher())
                ->append($this->parameters())
                ->append($this->ui())
            ->end()
        ;

        return $treeBuilder;
    }

    private function storage(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = (new TreeBuilder('storage'))->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('filesystem')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('dir')
                            ->defaultValue('%kernel.project_dir%/var/batch')
                        ->end()
                        ->scalarNode('serializer')
                            ->defaultValue('yokai_batch.job_execution_serializer.json')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dbal')
                    ->children()
                        ->scalarNode('connection')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('table')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('service')
                ->end()
            ->end()
        ;

        return $node;
    }

    private function launcher(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = (new TreeBuilder('launcher'))->getRootNode();

        $isInvalidDsn = fn(string $value) => \parse_url($value) === false
            || (\parse_url($value)['scheme'] ?? null) === null;

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('default')
                    ->defaultValue('simple')
                ->end()
                ->arrayNode('launchers')
                    ->defaultValue(['simple' => 'simple://simple'])
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()
                    ->validate()
                        ->ifTrue($isInvalidDsn)->thenInvalid('Invalid job launcher DSN.')
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function parameters(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = (new TreeBuilder('parameters'))->getRootNode();

        $isStringAssociativeArray = function (mixed $value): bool {
            if (!\is_array($value)) {
                return false;
            }

            foreach ($value as $key => $unused) {
                if (!\is_string($key)) {
                    return false;
                }
            }

            return true;
        };

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('global')
                    ->useAttributeAsKey('name')
                    ->variablePrototype()
                ->end()
                ->arrayNode('per_job')
                    ->useAttributeAsKey('name')
                    ->variablePrototype()
                    ->validate()
                        ->ifTrue(fn(mixed $value) => !$isStringAssociativeArray($value))
                            ->thenInvalid('Should be an array<string, mixed>')
                ->end()
            ->end()
        ;

        return $node;
    }

    private function ui(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = (new TreeBuilder('ui'))->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
                ->arrayNode('templating')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->always(function (string|array $value) {
                            if (\is_string($value)) {
                                $value = match ($value) {
                                    'bootstrap4' => ['prefix' => '@YokaiBatch/bootstrap4', 'service' => null],
                                    'sonata' => ['service' => 'yokai_batch.ui.sonata_templating', 'prefix' => null],
                                    default => throw new \InvalidArgumentException(
                                        \sprintf('Unknown templating shortcut "%s".', $value),
                                    ),
                                };
                            }

                            if (!isset($value['service']) && !isset($value['prefix'])) {
                                throw new \InvalidArgumentException(
                                    'You must either configure "service" or "prefix".',
                                );
                            } elseif (isset($value['service']) && isset($value['prefix'])) {
                                throw new \InvalidArgumentException(
                                    'You cannot configure "service" and "prefix" at the same time.',
                                );
                            }

                            return $value;
                        })
                    ->end()
                    ->children()
                        ->scalarNode('service')->defaultNull()->end()
                        ->scalarNode('prefix')->defaultValue('@YokaiBatch/bootstrap4')->end()
                        ->scalarNode('base_template')->defaultValue('base.html.twig')->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('attributes')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('list')
                                    ->defaultValue('IS_AUTHENTICATED')
                                ->end()
                                ->scalarNode('view')
                                    ->defaultValue('IS_AUTHENTICATED')
                                ->end()
                                ->scalarNode('traces')
                                    ->defaultValue('IS_AUTHENTICATED')
                                ->end()
                                ->scalarNode('logs')
                                    ->defaultValue('IS_AUTHENTICATED')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
