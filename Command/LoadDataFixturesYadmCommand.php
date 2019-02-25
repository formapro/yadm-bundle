<?php
namespace Formapro\Yadm\Bundle\Command;

use App\Kernel;
use Formapro\Yadm\Bundle\Doctrine\Fixture\YadmExecutor;
use Formapro\Yadm\Bundle\Doctrine\Fixture\YadmPurger;
use Formapro\Yadm\Bundle\Doctrine\YadmManager;
use Formapro\Yadm\Registry;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadDataFixturesYadmCommand extends Command
{
    public static $defaultName = 'yadm:fixtures:load';
    
    /**
     * @var Registry
     */
    private $yadm;

    /**
     * @var ContainerInterface 
     */
    private $container;

    public function __construct(?string $name = null, Registry $yadm, ContainerInterface $container)
    {
        parent::__construct($name);
        
        $this->yadm = $yadm;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Load data fixtures to your database.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('dump-reference-repository', null,
                InputOption::VALUE_OPTIONAL,
                'A file path where to store reference repository data as JSON.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new YadmManager($this->yadm);

        if ($input->isInteractive() && !$input->getOption('append')) {
            if (!$this->askConfirmation($input, $output, '<question>Careful, database will be purged. Do you want to continue y/N ?</question>', false)) {
                return;
            }
        }

        $dirOrFile = $input->getOption('fixtures');
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            /** @var Kernel $kernel */
            $kernel = $this->getApplication()->getKernel();

            $paths = [];
            $paths[] = $kernel->getProjectDir().'/src/DataFixtures/YADM';
            foreach ($kernel->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/YADM';
            }
        }

        $loader = new DataFixturesLoader($this->container);
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            } elseif (is_file($path)) {
                $loader->loadFromFile($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $purger = new YadmPurger($this->yadm);
        $executor = new YadmExecutor($manager, $purger);
        $executor->setLogger(function ($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));

        if ($dumpFile  = $input->getOption('dump-reference-repository')) {
            file_put_contents(
                $dumpFile,
                json_encode($executor->getReferenceRepository()->getIdentities())
            );

            $output->writeln(sprintf(
                '  <comment></comment> Reference repository was dumped (as JSON) to: : <info>%s<info>',
                realpath($dumpFile)
            ));
        }

        $output->writeln('Done');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $question
     * @param bool            $default
     *
     * @return bool
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default)
    {
        if (!class_exists(ConfirmationQuestion::class)) {
            $dialog = $this->getHelperSet()->get('dialog');

            return $dialog->askConfirmation($output, $question, $default);
        }

        $questionHelper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion($question, $default);

        return $questionHelper->ask($input, $output, $question);
    }
}