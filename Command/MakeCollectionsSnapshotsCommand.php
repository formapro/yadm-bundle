<?php
namespace Makasim\Yadm\Bundle\Command;

use Makasim\Yadm\Bundle\Snapshotter;
use Makasim\Yadm\Registry;
use Makasim\Yadm\Storage;
use MongoDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class MakeCollectionsSnapshotsCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('yadm:make-collections-snapshots')
            ->setDescription('Makes snapshots of mongodb collections')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $logger->debug('Make snapshots of mongodb collections');

        $snapshotter = new Snapshotter($this->getClient());
        foreach ($this->getRegistry()->getStorages() as $name => $storage) {
            // same storage could be present in registry several times. It is always registered with class as name
            // and could have several other aliases.
            if (false == class_exists($name)) {
                continue;
            }

            /** @var Storage $storage */

            $collection = $storage->getCollection();

            $snapshotter->make($collection, $logger);
        }

        $logger->debug('Done');
    }

    /**
     * @return Client|object
     */
    private function getClient()
    {
        return $this->container->get('yadm.client');
    }

    /**
     * @return Registry|object
     */
    private function getRegistry()
    {
        return $this->container->get('yadm');
    }
}