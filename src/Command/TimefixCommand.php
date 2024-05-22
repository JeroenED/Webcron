<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Job;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'webcron:timefix', description: 'Fixes the next run and lastrun times')]
class TimefixCommand extends Command
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
            ->addOption('exclude', 'x', InputOption::VALUE_IS_ARRAY + InputOption::VALUE_OPTIONAL, 'The ids of the jobs exclude')
            ->addOption('adjustment', 'a', InputOption::VALUE_REQUIRED, 'The amount of time to add or subtract from nextrun and last run times. Time can be a number of seconds or hh:mm:ss preceded with + or - (if not preceded + is taken)');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $exclude = $input->getOption('exclude');
        $adjustment = $this->parseAdjustment($input->getOption('adjustment'));
        if(!empty($exclude)) {
            $qb = $this->doctrine->getRepository(Job::class)->createQueryBuilder('job');
            $qb
                ->where($qb->expr()->notIn('job.id', ':ids'))
                ->setParameter('ids', $exclude);
            $results = $qb->getQuery()->getResult();
        } else {
            $results = $this->doctrine->getRepository(Job::class)->findAll();
        }
        foreach ($results as $job) {
            $job->setNextRun($job->getNextrun() + $adjustment);
            if($job->getLastrun() !== NULL) $job->setLastrun($job->getLastrun() + $adjustment);
        }
        $this->doctrine->getManager()->flush();
        return Command::SUCCESS;
    }

    private function parseAdjustment(string $adjustment): int
    {
        $time = $adjustment;
        if(in_array(substr($adjustment, 0, 1), ['+','-']) !== false) {
            $time = substr($adjustment, 1);
        }

        $time = explode(':', $time);
        if(count($time) === 1) {
            $seconds = $time[0];
        } elseif(count($time) === 2) {
            throw new \InvalidArgumentException('Ambigious time format');
        } elseif(count($time) === 3) {
            $seconds = $time[0] * 3600 + $time[1] * 60 + $time[2];
        }

        if(substr($adjustment, 0, 1) === '-') {
            $seconds = 0 - $seconds;
        }
        return $seconds;
    }
}
