<?php
namespace Makasim\Yadm\Bundle\DependencyInjection;

use Makasim\Yadm\ChangesCollector;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Registry;
use Makasim\Yadm\Storage;
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

        $container->register('yadm.changes_collector', ChangesCollector::class);

        $storages = [];
        $repositories = [];
        foreach ($config['models'] as $name => $modelConfig) {
            $container->register(sprintf('yadm.%s.collection', $name), Collection::class)
                ->setFactory([new Reference('yadm.client'), 'selectCollection'])
                ->addArgument($modelConfig['database'])
                ->addArgument($modelConfig['collection'])
            ;

            if (false == $hydratorId = $modelConfig['hydrator']) {
                $hydratorId = sprintf('yadm.%s.hydrator', $name);

                $container->register($hydratorId, Hydrator::class)->addArgument($modelConfig['class']);
            }

            $container->register(sprintf('yadm.%s.storage', $name), Storage::class)
                ->addArgument(new Reference(sprintf('yadm.%s.collection', $name)))
                ->addArgument(new Reference($hydratorId))
                ->addArgument(new Reference('yadm.changes_collector'))
            ;

            if ($modelConfig['pessimistic_lock']) {
                $container->register(sprintf('yadm.%s.pessimistic_lock_collection', $name), Collection::class)
                    ->setFactory([new Reference('yadm.client'), 'selectCollection'])
                    ->addArgument($modelConfig['database'])
                    ->addArgument($modelConfig['collection'].'_lock')
                ;

                $container->register(sprintf('yadm.%s.pessimistic_lock', $name))
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