<?php


namespace JeroenED\Webcron\Command;


use Doctrine\DBAL\Exception;
use JeroenED\Framework\Kernel;
use JeroenED\Webcron\Repository\Run;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends Command
{
    protected static $defaultName = 'cleanup';
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Cleanup runs')
            ->setHelp('This command cleans the runs table')
            ->addOption('jobid', 'j', InputOption::VALUE_IS_ARRAY + InputOption::VALUE_REQUIRED, 'The ids of the jobs to clean')
            ->addOption('maxage', 'm', InputOption::VALUE_REQUIRED, 'The maximum age of the oldest runs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $maxage = $input->getOption('maxage');
        $jobs = $input->getOption('jobid');
        $runRepo = new Run($this->kernel->getDbCon());
        try {
            $deleted = $runRepo->cleanupRuns($jobs, $maxage);
            $output->writeln('Deleted ' . $deleted . ' runs');
            return Command::SUCCESS;
        } catch(Exception $exception) {
            $output->writeln($exception->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}