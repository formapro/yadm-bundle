<?php
namespace Formapro\Yadm\Bundle\Test;

use Formapro\Yadm\Bundle\Snapshotter;
use Formapro\Yadm\Registry;
use Formapro\Yadm\Storage;
use MongoDB\Client;
use Symfony\Component\HttpKernel\Kernel;

trait YadmExtension
{
    protected function truncateStorages()
    {
        foreach ($this->getYadmRegistry()->getUniqueStorages() as $storage) {
            $storage->getCollection()->drop();
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
