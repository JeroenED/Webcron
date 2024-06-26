<?php


namespace App\Command;


use App\Entity\Run;
use Doctrine\DBAL\Exception;
use App\Repository\RunRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'webcron:cleanup', description: 'Cleanup runs')]
class CleanupCommand extends Command
{
    protected KernelInterface $kernel;
    protected ManagerRegistry $doctrine;

    public function __construct(KernelInterface $kernel, ManagerRegistry $doctrine)
    {
        $this->kernel = $kernel;
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption('jobid', 'j', InputOption::VALUE_IS_ARRAY + InputOption::VALUE_REQUIRED, 'The ids of the jobs to clean')
            ->addOption('maxage', 'm', InputOption::VALUE_REQUIRED, 'The maximum age of the oldest runs');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $maxage = $input->getOption('maxage');
        $jobs = $input->getOption('jobid');
        $runRepo = $this->doctrine->getRepository(Run::class);
        $deleted = $runRepo->cleanupRuns($jobs, $maxage);
        $output->writeln('Deleted ' . $deleted . ' runs');
        return Command::SUCCESS;
    }
}