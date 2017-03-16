<?php
namespace Makasim\Yadm\Bundle;

use MongoDB\Client;
use MongoDB\Collection;
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

    /**
     * @param Collection $collection
     * @param LoggerInterface|null $logger
     */
    public function make(Collection $collection, LoggerInterface $logger = null)
    {
        $logger  = $logger ?: new NullLogger();

        $collectionName = $collection->getCollectionName();
        $dbName = $collection->getDatabaseName();
        $snapshotDbName = $dbName.'_snapshot';

        $logger->debug(sprintf(
            'Copy documents from <info>%s.%s</info> to <info>%s.%s</info>',
            $dbName,
            $collectionName,
            $snapshotDbName,
            $collectionName
        ));

        $snapshotCollection = $this->client->selectCollection($snapshotDbName, $collectionName);
        $snapshotCollection->drop();
        foreach ($collection->find() as $document) {
            $snapshotCollection->insertOne($document);
        }
    }

    /**
     * @param Collection $collection
     * @param LoggerInterface|null $logger
     */
    public function restore(Collection $collection, LoggerInterface $logger = null)
    {
        $logger  = $logger ?: new NullLogger();

        $collectionName = $collection->getCollectionName();
        $dbName = $collection->getDatabaseName();
        $snapshotDbName = $dbName.'_snapshot';

        $logger->debug(sprintf(
            'Copy documents from <info>%s.%s</info> to <info>%s.%s</info>',
            $dbName,
            $collectionName,
            $snapshotDbName,
            $collectionName
        ));

        $snapshotCollection = $this->client->selectCollection($snapshotDbName, $collectionName);
        $collection->drop();
        foreach ($snapshotCollection->find() as $document) {
            $collection->insertOne($document);
        }
    }
}