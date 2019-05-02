<?php
namespace Makasim\Yadm\Bundle\Test;

use Makasim\Yadm\Bundle\Snapshotter;
use Makasim\Yadm\Registry;
use Makasim\Yadm\Storage;
use MongoDB\Client;
use Symfony\Component\HttpKernel\Kernel;

trait YadmExtension
{
    protected function truncateStorages()
    {
        $snapshotter = new Snapshotter($this->getMongodbClient());
        foreach ($this->getYadmRegistry()->getUniqueStorages() as $storage) {
            $snapshotter->delete($storage);
        }
    }

    protected function restoreStorages()
    {
        foreach ($this->getYadmRegistry()->getUniqueStorages() as $storage) {
            $this->restoreStorage($storage);
        }
    }

    protected function restoreStorage(Storage $storage)
    {
        $snapshotter = new Snapshotter($this->getMongodbClient());
        $snapshotter->restore($storage);
    }

    protected function getYadmRegistry(): Registry
    {
        return $this->getKernel()->getContainer()->get('yadm');
    }

    protected function getMongodbClient(): Client
    {
        return $this->getKernel()->getContainer()->get('yadm.client');
    }

    abstract protected function getKernel(): Kernel;
}
