<?php
namespace Makasim\Yadm\Bundle\DependencyInjection;

use Makasim\Yadm\ChangesCollector;
use Makasim\Yadm\CollectionFactory;
use Makasim\Yadm\PessimisticLock;
use Makasim\Yadm\Registry;
use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class YadmExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->register('yadm.client', Client::class)
            ->addArgument($config['mongo_uri'])
        ;

        $container->register('yadm.collection_factory', CollectionFactory::class)
            ->addArgument(new Reference('yadm.client'))
            ->addArgument($config['mongo_uri'])
        ;

        $container->register('yadm.changes_collector', ChangesCollector::class);

        $storages = [];
        $repositories = [];
        foreach ($config['models'] as $name => $modelConfig) {
            $container->register(sprintf('yadm.%s.collection', $name), Collection::class)
                ->setFactory([new Reference('yadm.collection_factory'), 'create'])
                ->addArgument($modelConfig['collection'])
                ->addArgument($modelConfig['database'])
            ;

            if (false == $hydratorId = $modelConfig['hydrator']) {
                $hydratorId = sprintf('yadm.%s.hydrator', $name);

                $hydratorClass = $modelConfig['hydrator_class'];

                $container->register($hydratorId, $hydratorClass)->addArgument($modelConfig['class']);
            }



            $container->register(sprintf('yadm.%s.storage', $name), $modelConfig['storage_class'])
                ->addArgument(new Reference(sprintf('yadm.%s.collection', $name)))
                ->addArgument(new Reference($hydratorId))
                ->addArgument(new Reference('yadm.changes_collector'))
            ;

            if ($modelConfig['pessimistic_lock']) {
                $container->register(sprintf('yadm.%s.pessimistic_lock_collection', $name), Collection::class)
                    ->setFactory([new Reference('yadm.collection_factory'), 'create'])
                    ->addArgument($modelConfig['collection'].'_lock')
                    ->addArgument($modelConfig['database'])
                ;

                $container->register(sprintf('yadm.%s.pessimistic_lock', $name), PessimisticLock::class)
                    ->addArgument(new Reference(sprintf('yadm.%s.pessimistic_lock_collection', $name)))
                ;

                $container->getDefinition(sprintf('yadm.%s.storage', $name))
                    ->addArgument(new Reference(sprintf('yadm.%s.pessimistic_lock', $name)))
                ;
            }

            if (isset($modelConfig['repository'])) {
                $repositories[$modelConfig['class']] = new Reference($modelConfig['repository']);
            }

            $storages[$modelConfig['class']] = new Reference(sprintf('yadm.%s.storage', $name));
            $storages[$name] = new Reference(sprintf('yadm.%s.storage', $name));
        }

        $container->register('yadm', Registry::class)
            ->addArgument($storages)
            ->addArgument($repositories)
        ;
    }
}