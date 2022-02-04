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
        $jobRepo->unlockJob();
        touch($this->kernel->getCacheDir() . '/daemon-running.lock');
        while(1) {
            if($endofscript !== false && time() > $endofscript) break;

            $maxwait = time() + 10;
            $nextrun = $jobRepo->getTimeOfNextRun();
            $sleepuntil = min($maxwait, $nextrun);
            if($sleepuntil > time()) time_sleep_until($sleepuntil);

            $jobsToRun = $jobRepo->getJobsDue();
            if(!empty($jobsToRun)) {
                foreach($jobsToRun as $job) {
                    $jobObj = $jobRepo->getJob($job['id']);
                    if($jobObj['data']['crontype'] == 'reboot') {
                        $str   = @file_get_contents('/proc/uptime');
                        $num   = floatval($str);
                        $rebootedself = ($num < $jobObj['data']['reboot-duration'] * 60);
                        $consolerun = $jobRepo->getTempVar($job['id'], 'consolerun', false);
                        if($consolerun && !$rebootedself) continue;
                    }
                    $jobRepo->setJobRunning($job['id'], true);
                    $output->writeln('Running Job ' . $job['id']);
                    declare(ticks = 1);
                    pcntl_signal(SIGCHLD, SIG_IGN);
                    $pid = pcntl_fork();
                    if($pid == -1) {
                        $jobRepo->RunJob($job['id'], $job['running'] == 2);
                        $jobRepo->setJobRunning($job['id'], false);
                    } elseif ($pid == 0) {
                        $dbcon = $this->kernel->getDbCon();
                        $dbcon->close();
                        $dbcon->connect();
                        $jobRepo->RunJob($job['id'], $job['running'] == 2);
                        $jobRepo->setJobRunning($job['id'], false);
                        exit;
                    }

                }
            }
        }
        $output->writeln('Ended after ' . $timelimit . ' seconds');
        pcntl_wait($status);

        unlink($this->kernel->getCacheDir() . '/daemon-running.lock');
        return Command::SUCCESS;
    }
}
