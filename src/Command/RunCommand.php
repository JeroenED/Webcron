<?php

namespace JeroenED\Webcron\Command;

use JeroenED\Framework\Kernel;
use JeroenED\Framework\Repository;
use JeroenED\Webcron\Repository\Job;
use JeroenED\Webcron\Repository\Run;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected static $defaultName = 'run';
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Run a single cronjob')
            ->setHelp('This command runs a single command')
            ->addArgument('jobid', InputArgument::REQUIRED, 'The id of the job to be run');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobRepo = new Job($this->kernel->getDbCon());
        $jobId = (int)$input->getArgument('jobid');
        $jobRepo->setJobRunning($jobId, true);
        $result = $jobRepo->runNow($jobId, true);
        $jobRepo->setJobRunning($jobId, false);
        $output->write($result['output']);
        if($result['success']) {
            $output->writeln('Job succeeded with  in ' . number_format($result['runtime'], 3) . 'secs exitcode ' . $result['exitcode']);
            return Command::SUCCESS;
        } else {
            $output->writeln('Job failed in ' . number_format($result['runtime'], 3) . 'secs with exitcode ' . $result['exitcode']);
            return Command::FAILURE;
        }
    }
}