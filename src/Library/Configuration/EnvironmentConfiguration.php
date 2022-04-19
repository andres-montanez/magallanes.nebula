<?php

namespace App\Library\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class EnvironmentConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('environment');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                // env
                ->arrayNode('env')
                    ->scalarPrototype()->end()
                ->end()

                // stages
                ->arrayNode('stages')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('docker')->defaultNull()->end()
                            ->arrayNode('steps')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('cmd')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                // package
                ->arrayNode('package')
                    ->children()
                        ->integerNode('user')->defaultNull()->end()
                        ->integerNode('group')->defaultNull()->end()
                        ->arrayNode('excludes')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()

                // buildsToKeep
                ->integerNode('buildsToKeep')
                    ->min(1)
                    ->defaultValue(10)
                ->end()

                // release
                ->arrayNode('release')
                    ->children()
                        ->integerNode('releasesToKeep')
                            ->min(1)
                            ->defaultValue(3)
                        ->end()
                        // deploy
                        ->arrayNode('deploy')
                            ->children()
                                ->enumNode('strategy')
                                    ->values(['scp'])
                                ->end()
                                ->scalarNode('path')->defaultNull()->end()
                                ->scalarNode('user')->defaultNull()->end()
                                ->arrayNode('hosts')
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end() // deploy
                        // post
                        ->arrayNode('post')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->defaultNull()->end()
                                    ->arrayNode('steps')
                                        ->arrayPrototype()
                                            ->children()
                                                ->scalarNode('cmd')->end()
                                            ->end()
                                        ->end()
                                    ->end() // steps
                                ->end()
                            ->end()
                        ->end() // post
                    ->end()
                ->end()
            // --
            ->end()
        ;

        return $treeBuilder;
    }
}
