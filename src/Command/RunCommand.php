<?php

namespace App\Command;

use App\Entity\Job;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'webcron:run', description: 'Run a single cronjob')]
class RunCommand extends Command
{
    protected $kernel;
    protected $doctrine;

    public function __construct(KernelInterface $kernel, ManagerRegistry $doctrine)
    {
        $this->kernel = $kernel;
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp('This command runs a single command')
            ->addArgument('jobid', InputArgument::REQUIRED, 'The id of the job to be run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobRepo = $this->doctrine->getRepository(Job::class);
        $jobId = (int)$input->getArgument('jobid');
        $job = $jobRepo->find($jobId);
        if($job === NULL) {
            $output->writeln('Job does not exist');
            return Command::FAILURE;
        }
        $jobRunning = $jobRepo->isLockedJob($job);
        if($jobRunning) {
            $output->writeln('Job is already running');
            return Command::FAILURE;
        }
        $jobRepo->setJobRunning($job, true);
        $jobRepo->setTempVar($job, 'consolerun', true);
        $result = $jobRepo->runNow($job, true);
        if($job->getData('crontype') == 'reboot') {
            $sleeping = true;
            while($sleeping) {
                if(time() >= $job->getRunning()) $sleeping = false;
                sleep(1);
            }
            $result = $jobRepo->runNow($job, true);
        }
        $jobRepo->setJobRunning($job, false);
        $jobRepo->setTempVar($job, 'consolerun', false);
        $output->write($result['output']);
        if($result['success']) {
            $output->writeln('Job succeeded with  in ' . number_format($result['runtime'], 3) . 'secs with exitcode ' . $result['exitcode']);
            return Command::SUCCESS;
        } else {
            $output->writeln('Job failed in ' . number_format($result['runtime'], 3) . 'secs with exitcode ' . $result['exitcode']);
            return Command::FAILURE;
        }
    }
}