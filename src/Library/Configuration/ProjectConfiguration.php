<?php

namespace App\Library\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ProjectConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('project');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                // env
                ->arrayNode('env')
                    ->scalarPrototype()->end()
                ->end()

                // docker
                ->arrayNode('docker')
                    ->children()
                        ->integerNode('memory')->defaultValue(1024)->end()
                    ->end()
                ->end()

                // post
                ->arrayNode('post')
                    ->children()
                        // Failure
                        ->arrayNode('success')
                            ->children()
                                ->append($this->addSlack())
                            ->end()
                        ->end()

                        // Success
                        ->arrayNode('failure')
                            ->children()
                                ->append($this->addSlack())
                            ->end()
                        ->end()

                    ->end()
                ->end()

            // --
            ->end()
        ;

        return $treeBuilder;
    }

    public function addSlack()
    {
        $treeBuilder = new TreeBuilder('slack');
        $node = $treeBuilder->getRootNode();
        $node
            ->children()
                ->scalarNode('message')->end()
                ->arrayNode('channels')
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}