<?php
namespace Makasim\Yadm\Bundle\Command;

use Makasim\Yadm\Registry;
use Makasim\Yadm\Storage;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\CommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaUpdateCommand extends Command
{
    public static $defaultName = 'yadm:schema:update';
    
    /**
     * @var Registry
     */
    private $yadm;

    /**
     * @var Client
     */
    private $mongodb;

    /**
     * @var Database
     */
    private $database;

    public function __construct(?string $name = null, Registry $yadm, Client $mongodb)
    {
        parent::__construct($name);
        
        $this->yadm = $yadm;
        $this->mongodb = $mongodb;
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
            ->addOption('ignore-collection-exist-exception', null, InputOption::VALUE_NONE, 'Ignores already existing collections.')
            ->addOption('ignore-duplicate-key-exception', null, InputOption::VALUE_NONE, 'Ignores duplicate keys exception.')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /** @var Storage $storage */
        $storages = $this->yadm->getStorages();
        $storage = array_pop($storages);

        $this->database = $this->mongodb->selectDatabase($storage->getCollection()->getDatabaseName());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('drop')) {
            $this->dropDatabase($output);
        }

        $this->setupCreateCollections($output, $input->getOption('ignore-collection-exist-exception'));
        $this->setupLockIndexes($output);
        $this->setupModelIndexes($output, $input->getOption('ignore-duplicate-key-exception'));
    }

    private function setupCreateCollections(OutputInterface $output, bool $ignoreCollectionExistException)
    {
        $output->writeln('Create collections', OutputInterface::VERBOSITY_NORMAL);

        foreach ($this->yadm->getUniqueStorages() as $storage) {
            $meta = $storage->getMeta();
            try {
                $this->database->createCollection($storage->getCollection()->getCollectionName(), $meta->getCreateCollectionOptions());

                $output->writeln("\t> " . $storage->getCollection()->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
            } catch (CommandException $e) {
                if (false == $ignoreCollectionExistException) {
                    throw $e;
                }

                $output->writeln('<error>EXCEPTION</error> - '.$e->getMessage());
            }

            if ($lock = $storage->getPessimisticLock()) {
                try {
                    $this->database->createCollection($lock->getCollection()->getCollectionName());

                    $output->writeln("\t> ".$lock->getCollection()->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
                } catch (CommandException $e) {
                    if (false == $ignoreCollectionExistException) {
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

        foreach ($this->yadm->getUniqueStorages() as $storage) {
            if ($lock = $storage->getPessimisticLock()) {
                $lock->createIndexes();
                $output->writeln("\t> ".$lock->getCollection()->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
            }
        }
    }

    private function setupModelIndexes(OutputInterface $output, bool $ingoreDuplicateKeyException)
    {
        $output->writeln('Creating indexes');

        foreach ($this->yadm->getUniqueStorages() as $storage) {
            $collection = $storage->getCollection();
            if ($indexes = $storage->getMeta()->getIndexes()) {
                foreach ($indexes as $index) {
                    try {
                        $collection->createIndex($index->getKey(), $index->getOptions());

                        $output->writeln("\t> ".$collection->getCollectionName(), OutputInterface::VERBOSITY_DEBUG);
                    } catch (CommandException $e) {
                        if (false == $ingoreDuplicateKeyException) {
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
