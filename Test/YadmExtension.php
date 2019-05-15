<?php
namespace Formapro\Yadm\Bundle\Test;

use Formapro\Yadm\Bundle\Snapshotter;
use Formapro\Yadm\Registry;
use Formapro\Yadm\Storage;
use MongoDB\Client;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;

trait YadmExtension
{
    protected function truncateStorages()
    {
        $snapshotter = new Snapshotter($this->getMongodbClient());
        foreach ($this->getYadmRegistry()->getStorages() as $storage) {
            $snapshotter->delete($storage);
        }
    }

    protected function restoreStorages()
    {
        foreach ($this->getYadmRegistry()->getStorages() as $storage) {
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
        return $this->getTestContainer()->get('yadm');
    }

    protected function getMongodbClient(): Client
    {
        return $this->getTestContainer()->get('yadm.client');
    }

    abstract protected function getTestContainer(): TestContainer;
}
