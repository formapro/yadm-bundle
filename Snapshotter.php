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
        $snapshotCollectionName = 'snapshot_'.$collectionName;

        $logger->debug(sprintf(
            'Copy documents from <info>%s.%s</info> to <info>%s.%s</info>',
            $dbName,
            $collectionName,
            $dbName,
            $snapshotCollectionName
        ));

        $snapshotCollection = $this->client->selectCollection($dbName, $collectionName);
        $snapshotCollection->aggregate([['$match' => new \stdClass], ['$out' => $snapshotCollectionName ]]);
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
        $snapshotCollectionName = 'snapshot_'.$collectionName;

        $logger->debug(sprintf(
            'Copy documents from <info>%s.%s</info> to <info>%s.%s</info>',
            $dbName,
            $collectionName,
            $dbName,
            $snapshotCollectionName
        ));

        $snapshotCollection = $this->client->selectCollection($dbName, $snapshotCollectionName);
        $snapshotCollection->aggregate([['$match' => new \stdClass], ['$out' => $collectionName ]]);
    }
}