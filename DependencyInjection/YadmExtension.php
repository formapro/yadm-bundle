<?php
namespace Formapro\Yadm\Bundle\DependencyInjection;

use Formapro\Yadm\Bundle\Command\LoadDataFixturesYadmCommand;
use Formapro\Yadm\Bundle\Command\MakeCollectionsSnapshotsCommand;
use Formapro\Yadm\Bundle\Command\SchemaUpdateCommand;
use Formapro\Yadm\ChangesCollector;
use Formapro\Yadm\ClientProvider;
use Formapro\Yadm\CollectionFactory;
use Formapro\Yadm\ConvertValues;
use Formapro\Yadm\Migration\Symfony\MigrationsDIFactory;
use Formapro\Yadm\PessimisticLock;
use Formapro\Yadm\Registry;
use Formapro\Yadm\Type\UTCDatetimeType;
use Formapro\Yadm\Type\UuidType;
use Formapro\Yadm\LocatorRegistry;
use Formapro\Yadm\LazyClient;
use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class YadmExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->register('yadm.client_provider', ClientProvider::class)
            ->addArgument($config['mongo_uri'])
        ;

        $container->register('yadm.client', Client::class)
            ->setFactory([new Reference('yadm.client_provider'), 'getClient'])
        ;

        $container->register('yadm.uuid_type', UuidType::class);
        $container->register('yadm.utc_datetime', UTCDatetimeType::class);

        $container->register('yadm.collection_factory', CollectionFactory::class)
            ->addArgument(new Reference('yadm.client_provider'))
            ->addArgument($config['mongo_uri'])
        ;

        $container->register('yadm.changes_collector', ChangesCollector::class);
        
        $storages = [];
        foreach ($config['models'] as $name => $modelConfig) {
            $container->register(sprintf('yadm.%s.collection', $name), Collection::class)
                ->setFactory([new Reference('yadm.collection_factory'), 'create'])
                ->addArgument($modelConfig['collection'])
                ->addArgument($modelConfig['database'])
            ;

            $typesServices = [];
            foreach ($modelConfig['types'] as $key => $type) {
                switch ($type) {
                    case 'uuid':
                        $typesServices[$key] = new Reference('yadm.uuid_type');

                        break;
                    case 'datetime':
                        $typesServices[$key] = new Reference('yadm.utc_datetime');

                        break;
                    default:
                        $typesServices[$key] = new Reference($type);
                }
            }
            $container->register(sprintf('yadm.%s.convert_values', $name), ConvertValues::class)
                ->addArgument($typesServices)
            ;

            if (false == $hydratorId = $modelConfig['hydrator']) {
                $hydratorId = sprintf('yadm.%s.hydrator', $name);

                $hydratorClass = $modelConfig['hydrator_class'];

                $container->register($hydratorId, $hydratorClass)->addArgument($modelConfig['class']);
            }

            $storageMetaId = null;
            if (false == $storageMetaId = $modelConfig['storage_meta']) {
                if ($storageMetaId = $modelConfig['storage_meta_class']) {
                    $storageMetaId = sprintf('yadm.%s.storage_meta', $name);

                    $container->register($storageMetaId, $modelConfig['storage_meta_class']);
                }
            }

            $container->register(sprintf('yadm.%s.storage', $name), $modelConfig['storage_class'])
                ->addArgument(new Reference(sprintf('yadm.%s.collection', $name)))
                ->addArgument(new Reference($hydratorId))
                ->addArgument(new Reference('yadm.changes_collector'))
                ->addArgument(null)
                ->addArgument(new Reference(sprintf('yadm.%s.convert_values', $name)))
                ->addArgument($storageMetaId ? new Reference($storageMetaId) : null)
            ;

            if ($modelConfig['storage_autowire']) {
                $container->setAlias($modelConfig['storage_class'], sprintf('yadm.%s.storage', $name))
                    ->setPublic(true)
                ;
            }

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
                    ->replaceArgument(3, new Reference(sprintf('yadm.%s.pessimistic_lock', $name)))
                ;
            }

            $storages[$modelConfig['class']] = new Reference(sprintf('yadm.%s.storage', $name));
            $storages[$name] = new Reference(sprintf('yadm.%s.storage', $name));
        }

        $locator = $container->register('yadm.storage.locator', ServiceLocator::class)
            ->setArgument(0, $storages)
            ->addTag('container.service_locator')
        ;

        $container->register('yadm', LocatorRegistry::class)
            ->setArgument(0, array_keys($storages))
            ->setArgument(1, $locator)
        ;

        $container->addAliases([
            Registry::class => 'yadm',
            Client::class => 'yadm.client',
            ClientProvider::class => 'yadm.client_provider',
        ]);

        $container->register(LoadDataFixturesYadmCommand::class)
            ->addArgument(null)
            ->addArgument(new Reference('yadm'))
            ->addTag('console.command')
        ;

        $container->register(MakeCollectionsSnapshotsCommand::class)
            ->addArgument(null)
            ->addArgument(new Reference('yadm'))
            ->addArgument(new Reference('yadm.client_provider'))
            ->addTag('console.command')
        ;

        $container->register(SchemaUpdateCommand::class)
            ->addArgument(null)
            ->addArgument(new Reference('yadm'))
            ->addArgument(new Reference('yadm.client_provider'))
            ->addTag('console.command')
        ;

        // migrations
        MigrationsDIFactory::buildServices($config['migrations'], $container);
    }
}