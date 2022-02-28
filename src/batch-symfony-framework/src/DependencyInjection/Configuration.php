<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        /** @var ArrayNodeDefinition $root */
        $root = ($treeBuilder = new TreeBuilder('yokai_batch'))->getRootNode();

        $root
            ->children()
            ->append($this->storage())
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
}
