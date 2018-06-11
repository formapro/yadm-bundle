<?php
namespace Makasim\Yadm\Bundle;

use Makasim\Yadm\Storage;
use MongoDB\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Snapshotter
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function make(Storage $storage, LoggerInterface $logger = null)
    {
        $logger  = $logger ?: new NullLogger();

        $collection = $storage->getCollection();

        $collectionName = $collection->getCollectionName();
        $dbName = $collection->getDatabaseName();
        $snapshotDbName = $dbName.'_snapshot';

        $this->client->selectCollection($snapshotDbName, $collectionName)->drop();

        $logger->debug(sprintf(
            'Copy documents from <info>%s.%s</info> to <info>%s.%s</info>',
            $dbName,
            $collectionName,
            $snapshotDbName,
            $collectionName
        ));

        $snapshotCollection = $this->client->selectCollection($snapshotDbName, $collectionName);
        if ($documents = $collection->find()->toArray()) {
            $snapshotCollection->insertMany($documents);
        }
    }

    public function restore(Storage $storage, LoggerInterface $logger = null)
    {
        $logger  = $logger ?: new NullLogger();

        $collection = $storage->getCollection();

        $collectionName = $collection->getCollectionName();
        $dbName = $collection->getDatabaseName();
        $snapshotDbName = $dbName.'_snapshot';

        $collection->drop();

        $this->client->selectDatabase($dbName)->createCollection($collectionName, $storage->getMeta()->getCreateCollectionOptions());
        foreach ($storage->getMeta()->getIndexes() as $index) {
            $collection->createIndex($index->getKey(), $index->getOptions());
        }

        $logger->debug(sprintf(
            'Copy documents from <info>%s.%s</info> to <info>%s.%s</info>',
            $dbName,
            $collectionName,
            $snapshotDbName,
            $collectionName
        ));

        $snapshotCollection = $this->client->selectCollection($snapshotDbName, $collectionName);
        if ($documents = $snapshotCollection->find()->toArray()) {
            $collection->insertMany($documents);
        }
    }
}