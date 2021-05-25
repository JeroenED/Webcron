<?php


namespace JeroenED\Webcron\Command;

use JeroenED\Framework\Kernel;
use JeroenED\Webcron\Repository\Job;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DaemonCommand extends Command
{

    protected static $defaultName = 'daemon';
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('The deamon slayer of webcron')
            ->setHelp('This command is the daemon process of webcron, enabling webcron to actually run jobs on time')
            ->addOption('time-limit', 't', InputOption::VALUE_REQUIRED, 'Time limit in seconds before stopping the daemon.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobRepo = new Job($this->kernel->getDbCon());
        $timelimit = $input->getOption('time-limit') ?? false;
        if ($timelimit === false) {
            $endofscript = false;
        } elseif(is_numeric($timelimit)) {
            $endofscript = time() + $timelimit;
        } else {
            throw new \InvalidArgumentException('Time limit has incorrect value');
        }

        while(1) {
            if($endofscript !== false && time() > $endofscript) break;
            $jobsToRun = $jobRepo->getJobsDue();
            if(!empty($jobsToRun)) {
                foreach($jobsToRun as $job) {
                    $jobRepo->setJobRunning($job, true);
                    $output->writeln('Runnig Job ' . $job);
                    $pid = pcntl_fork();
                    if($pid == -1) {
                        $jobRepo->RunJob($job);
                        $jobRepo->setJobRunning($job, false);
                    } elseif ($pid == 0) {
                        $dbcon = $this->kernel->getDbCon();
                        $dbcon->close();
                        $dbcon->connect();
                        $jobRepo->RunJob($job);
                        $jobRepo->setJobRunning($job, false);
                        exit;
                    }

                }
            }
            sleep(1);
        }
        $output->writeln('Ended after ' . $timelimit . ' seconds');
        return Command::SUCCESS;
    }
}