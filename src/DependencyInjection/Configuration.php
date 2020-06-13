<?php

declare(strict_types=1);

namespace Bizkit\VersioningBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bizkit_versioning');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('parameter_prefix')
                    ->info('The prefix added to the version parameters.')
                    ->example('my_app')
                    ->cannotBeEmpty()
                    ->defaultValue('application')
                ->end()
                ->scalarNode('strategy')
                    ->info('The versioning strategy used.')
                    ->cannotBeEmpty()
                    ->defaultValue('incrementing')
                ->end()

                ->scalarNode('filename')
                    ->info('The name of the file containing the version information.')
                    ->cannotBeEmpty()
                    ->defaultValue('version')
                ->end()
                ->scalarNode('filepath')
                    ->info('The path to the file containing the version information.')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/config')
                ->end()

                ->enumNode('format')
                    ->info('The format used for the version file.')
                    ->values(['yaml', 'xml'])
                    ->cannotBeEmpty()
                    ->defaultValue('yaml')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
