<?php
namespace Makasim\Yadm\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('yadm');

        $rootNode->children()
            ->scalarNode('mongo_uri')->end()
            ->arrayNode('models')
                ->prototype('array')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('collection')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('database')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('hydrator')->defaultValue(false)->end()
                        ->booleanNode('pessimistic_lock')->defaultFalse()->end()
                        ->scalarNode('repository')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tb;
    }
}