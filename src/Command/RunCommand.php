<?php

namespace App\Command;

use App\Repository\JobRepository;
use App\Repository\RunRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RunCommand extends Command
{
    protected static $defaultName = 'run';
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('RunRepository a single cronjob')
            ->setHelp('This command runs a single command')
            ->addArgument('jobid', InputArgument::REQUIRED, 'The id of the job to be run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runRepo = $this->getEntityManager()->getRepository(Run::class);
        $jobId = (int)$input->getArgument('jobid');
        $jobRunning = $jobRepo->isLockedJob($jobId);
        if($jobRunning) {
            $output->writeln('JobRepository is already running');
            return Command::FAILURE;
        }
        $jobRepo->setJobRunning($jobId, true);
        $jobRepo->setTempVar($jobId, 'consolerun', true);
        $result = $jobRepo->runNow($jobId, true);
        $job = $jobRepo->getJob($jobId);
        if($job['data']['crontype'] == 'reboot') {
            $sleeping = true;
            while($sleeping) {
                $job = $jobRepo->getJob($jobId);
                if(time() >= $job['running']) $sleeping = false;
                sleep(1);
            }
            $result = $jobRepo->runNow($jobId, true);
        }
        $jobRepo->setJobRunning($jobId, false);
        $jobRepo->setTempVar($jobId, 'consolerun', false);
        $output->write($result['output']);
        if($result['success']) {
            $output->writeln('JobRepository succeeded with  in ' . number_format($result['runtime'], 3) . 'secs with exitcode ' . $result['exitcode']);
            return Command::SUCCESS;
        } else {
            $output->writeln('JobRepository failed in ' . number_format($result['runtime'], 3) . 'secs with exitcode ' . $result['exitcode']);
            return Command::FAILURE;
        }
    }
}