<?php
namespace Formapro\Yadm\Bundle\DependencyInjection;

use Formapro\Yadm\Hydrator;
use Formapro\Yadm\Migration\Symfony\MigrationsDIFactory;
use Formapro\Yadm\Storage;
use Formapro\Yadm\StorageMeta;
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
            ->scalarNode('mongo_uri')->cannotBeEmpty()->isRequired()->end()
            ->arrayNode('models')
                ->prototype('array')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('types')
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('storage_class')->defaultValue(Storage::class)->cannotBeEmpty()->end()
                        ->scalarNode('storage_meta')->defaultValue(false)->end()
                        ->scalarNode('storage_meta_class')->defaultValue(StorageMeta::class)->cannotBeEmpty()->end()
                        ->booleanNode('storage_autowire')->defaultFalse()->end()
                        ->scalarNode('collection')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('database')->defaultValue(null)->end()
                        ->scalarNode('hydrator')->defaultValue(false)->end()
                        ->scalarNode('hydrator_class')->defaultValue(Hydrator::class)->cannotBeEmpty()->end()
                        ->booleanNode('pessimistic_lock')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
            ->append(MigrationsDIFactory::getConfiguration())
        ;

        return $tb;
    }
}