<?php
namespace Formapro\Yadm\Bundle\Command;

use Formapro\Yadm\Bundle\Snapshotter;
use Formapro\Yadm\Registry;
use Formapro\Yadm\Storage;
use MongoDB\Client;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCollectionsSnapshotsCommand extends Command
{
    public static $defaultName = 'yadm:make-collections-snapshots';
    
    private $container;

    public function __construct(?string $name = null, ContainerInterface $cotainer)
    {
        parent::__construct($name);

        $this->container = $cotainer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Makes snapshots of mongodb collections')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $logger->info('Make collection\s snapshots');

        $snapshotter = new Snapshotter($this->getClient());
        foreach ($this->getRegistry()->getStorages() as $storage) {
            $snapshotter->make($storage, $logger);
        }

        $logger->info('Done');
    }
    
    protected function getClient(): Client
    {
        return $this->container->get('yadm.client');
    }
    
    protected function getRegistry(): Registry
    {
        return $this->container->get('yadm');
    }
}
