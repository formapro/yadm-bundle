<?php
namespace Makasim\Yadm\Bundle\Doctrine\Fixture;

use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Makasim\Yadm\Registry;
use Makasim\Yadm\Storage;

class YadmPurger implements PurgerInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        foreach ($this->registry->getStorages() as $storage) {
            /** @var Storage $storage */

            $storage->getCollection()->drop();
        }
    }
}
