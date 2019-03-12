<?php
namespace Formapro\Yadm\Bundle\Command;

use Formapro\Yadm\Registry;
use Formapro\Yadm\Storage;
use Formapro\Yadm\ClientProvider;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\CommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Container\ContainerInterface;

class SchemaUpdateCommand extends Command
{
    public static $defaultName = 'yadm:schema:update';

    private $registry;

    private $clientProvider;

    /**
     * @var Database
     */
    private $database;

    public function __construct(?string $name = null, Registry $registry, ClientProvider $clientProvider)
    {
        parent::__construct($name);

        $this->registry = $registry;
        $this->clientProvider = $clientProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Update database schema.')
            ->addOption('drop', null, InputOption::VALUE_NONE, 'Setup command loads fixtures if option set.')
            ->addOption('stop-on-collection-exist-exception', null, InputOption::VALUE_NONE, 'Interrupts execution if collection already exists.')
            ->addOption('stop-on-duplicate-key-exception', null, InputOption::VALUE_NONE, 'Interrupts execution on duplicate keys exception.')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /** @var Storage $storage */
        $storages = $this->registry->getStorages();
        $storage = array_pop($storages);

        $this->database = $this->clientProvider->getClient()->selectDatabase($storage->getCollection()->getDatabaseName());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('drop')) {
            $this->dropDatabase($output);
        }

        $this->setupCreateCollections($output, $input->getOption('stop-on-collection-exist-exception'));
        $this->setupLockIndexes($output);
        $this->setupModelIndexes($output, $input->getOption('stop-on-duplicate-key-exception'));
    }

    private function setupCreateCollections(OutputInterface $output, bool $stopOnCollectionExistException)
    {
        $output->writeln('Create collections', OutputInterface::VERBOSITY_NORMAL);

        foreach ($this->registry->getStorages() as $storage) {
            $meta = $storage->getMeta();
            try {
                $this->database->createCollection($storage->getCollection()->getCollectionName(), $meta->getCreateCollectionOptions());

                $output->writeln("\t> " . $storage->getCollection()->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
            } catch (CommandException $e) {
                if ($stopOnCollectionExistException) {
                    throw $e;
                }

                $output->writeln('<error>EXCEPTION</error> - '.$e->getMessage());
            }

            if ($lock = $storage->getPessimisticLock()) {
                try {
                    $this->database->createCollection($lock->getCollection()->getCollectionName());

                    $output->writeln("\t> ".$lock->getCollection()->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
                } catch (CommandException $e) {
                    if ($stopOnCollectionExistException) {
                        throw $e;
                    }

                    $output->writeln('<error>EXCEPTION</error> - '.$e->getMessage());
                }
            }
        }
    }

    private function setupLockIndexes(OutputInterface $output)
    {
        $output->writeln('Creating lock indexes', OutputInterface::VERBOSITY_NORMAL);

        foreach ($this->registry->getStorages() as $storage) {
            if ($lock = $storage->getPessimisticLock()) {
                $lock->createIndexes();
                $output->writeln("\t> ".$lock->getCollection()->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
            }
        }
    }

    private function setupModelIndexes(OutputInterface $output, bool $stopOnDuplicateKeyException)
    {
        $output->writeln('Creating indexes');

        foreach ($this->registry->getStorages() as $storage) {
            $collection = $storage->getCollection();
            if ($indexes = $storage->getMeta()->getIndexes()) {
                foreach ($indexes as $index) {
                    try {
                        $collection->createIndex($index->getKey(), $index->getOptions());

                        $output->writeln("\t> ".$collection->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
                    } catch (CommandException $e) {
                        if ($stopOnDuplicateKeyException) {
                            throw $e;
                        }

                        $output->writeln('<error>EXCEPTION</error> - '.$e->getMessage());
                    }
                }
            }
        }
    }

    private function dropDatabase(OutputInterface $output)
    {
        $output->writeln('Drop database <info>'.$this->database->getDatabaseName().'</info>');
        $output->writeln('');

        $this->database->drop();
    }
}
