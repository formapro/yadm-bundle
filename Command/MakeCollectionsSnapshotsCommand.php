<?php
namespace Formapro\Yadm\Bundle\Command;

use Formapro\Yadm\Bundle\Snapshotter;
use Formapro\Yadm\ClientProvider;
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
    
    private $registry;
    
    private $clientProvider;

    public function __construct(Registry $registry, ClientProvider $clientProvider)
    {
        $this->registry = $registry;
        $this->clientProvider = $clientProvider;

        parent::__construct(self::$defaultName);
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

        $snapshotter = new Snapshotter($this->clientProvider->getClient());
        foreach ($this->registry->getStorages() as $storage) {
            $snapshotter->make($storage, $logger);
        }

        $logger->info('Done');
    }
}
