<?php
namespace Makasim\Yadm\Bundle\Test;

use Makasim\Yadm\Bundle\Snapshotter;
use Makasim\Yadm\Registry;
use MongoDB\Client;
use Symfony\Component\HttpKernel\Kernel;

trait YadmExtension
{
    protected function restoreSnapshots()
    {
        $snapshotter = new Snapshotter($this->getMongodbClient());

        foreach ($this->getYadmRegistry()->getStorages() as $name => $storage) {
            if (false ==class_exists($name)) {
                continue;
            }

            $snapshotter->restore($storage->getCollection());
        }
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
