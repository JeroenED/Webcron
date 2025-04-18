<?php


namespace App\Command;

use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'webcron:daemon', description: 'The master script of Webcron Management')]
class DaemonCommand extends Command
{
    protected ContainerBagInterface $containerBag;
    protected ManagerRegistry $doctrine;

    public function __construct(ContainerBagInterface $containerBag, ManagerRegistry $doctrine)
    {
        $this->containerBag = $containerBag;
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp('This command is the daemon process of webcron, enabling webcron to actually run jobs on time')
            ->addOption('time-limit', 't', InputOption::VALUE_REQUIRED, 'Time limit in seconds before stopping the daemon.')
            ->addOption('async', 'a', InputOption::VALUE_NEGATABLE, 'Time limit in seconds before stopping the daemon.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        ini_set('memory_limit', '4G');
        $jobRepo = $this->doctrine->getRepository(Job::class);
        $timelimit = $input->getOption('time-limit') ?? false;
        $async = $input->getOption('async') ?? function_exists('pcntl_fork');
        if ($timelimit === false) {
            $endofscript = false;
        } elseif(is_numeric($timelimit)) {
            $endofscript = time() + $timelimit;
        } else {
            throw new \InvalidArgumentException('Time limit has incorrect value');
        }
        $jobRepo->unlockJob();
        file_put_contents($this->containerBag->get('pidfile'), time());
        while(1) {
            if($endofscript !== false && time() > $endofscript) break;

            $jobsToRun = $jobRepo->getJobsDue();
            if(!empty($jobsToRun)) {
                foreach($jobsToRun as $key=>$job) {
                    if ($job->getData('crontype') == 'reboot') {
                        $str = @file_get_contents('/proc/uptime');
                        $num = floatval($str);
                        $rebootedself = ($num < $job->getData('reboot-duration') * 60);
                        $consolerun = $jobRepo->getTempVar($job, 'consolerun', false);
                        if ($consolerun && !$rebootedself) continue;
                    }
                    $manual = '';
                    if($jobRepo->getTempVar($job, 'webhook', false)) {
                        $manual = 'Webhook';
                    } elseif($job->getRunning() > 1) {
                        $manual = 'Manual';
                    };
                    $jobRepo->setJobRunning($job, true);
                    $output->writeln('Running Job ' . $job->getId());
                    if($async) {
                        declare(ticks = 1);
                        pcntl_signal(SIGCHLD, SIG_IGN);
                        $pid = pcntl_fork();
                        $this->doctrine->getConnection()->close();
                        $jobRepo = $this->doctrine->getRepository(Job::class);
                    }

                    if((!$async || $pid == -1) || $pid == 0) {
                        $result = $jobRepo->RunJob($job, $manual);
                        if ($result['status'] == 'ran') $jobRepo->setJobRunning($job, false);
                        if (isset($pid) && $pid == 0) exit;
                    }
                    unset($jobsToRun[$key]);
                    unset($job);
                }
            }
            $this->doctrine->getManager()->clear();
            file_put_contents($this->containerBag->get('pidfile'), time());

            $maxwait = time() + 30;
            $nextrun = max($jobRepo->getTimeOfNextRun(), time() + 1);
            $sleepuntil = min($maxwait, $nextrun);
            if($sleepuntil > time()) time_sleep_until($sleepuntil);
            gc_collect_cycles();
        }
        $output->writeln('Ended after ' . $timelimit . ' seconds');
        pcntl_wait($status);

        unlink($this->containerBag->get('pidfile'));
        return Command::SUCCESS;
    }
}
