<?php
namespace Makasim\Yadm\Bundle\Doctrine\Fixture;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Makasim\Yadm\Bundle\Doctrine\YadmManager;

class YadmExecutor extends AbstractExecutor
{
    /**
     * @var YadmManager
     */
    private $manager;

    /**
     * @param YadmManager $manager
     * @param YadmPurger|null $purger
     */
    public function __construct(YadmManager $manager, YadmPurger $purger = null)
    {
        $this->manager = $manager;
        $this->purger = $purger;

        parent::__construct($manager);

        $this->referenceRepository = new ReferenceRepository($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $fixtures, $append = false)
    {
        if ($append === false && $this->purger) {
            $this->purger->purge();
        }

        foreach ($fixtures as $fixture) {
            $this->load($this->manager, $fixture);
        }
    }
}
