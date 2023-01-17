<?php


namespace App\Repository;


use App\Entity\Job;
use App\Entity\Run;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityRepository;

class RunRepository extends EntityRepository
{
    const FAILED = 'F';
    const SUCCESS = 'S';
    const MANUAL = 'M';
    const TRIGGERED = 'T';

    public function getRunsForJob(Job $job, bool $onlyfailed = false, int $maxage = NULL, bool $ordered = true): array
    {
        $qb = $this->createQueryBuilder('run');
        $runs = $qb
            ->where('run.job = :job')
            ->setParameter(':job', $job);

        if ($onlyfailed) {
            $runs = $runs->andWhere('run.flags LIKE :flags')->setParameter(':flags', '%' . RunRepository::FAILED . '%');
        }
        if($maxage !== NULL) {
            $runs = $runs->andWhere('run.timestamp > :timestamp')->setParameter(':timestamp', time() - ($maxage * 24 * 60 * 60));
        }
        if ($ordered) $runs->orderBy('run.timestamp', 'DESC');
        return $runs->getQuery()->getResult();
    }

    public function addRun(Job $job, string $exitcode, int $starttime, float $runtime, string $output, array $flags): void
    {
        $em = $this->getEntityManager();

        $run = new Run();
        $run
            ->setJob($job)
            ->setExitcode($exitcode)
            ->setTimestamp($starttime)
            ->setRuntime($runtime)
            ->setOutput($output)
            ->setFlags(implode($flags));
        $em->persist($run);
        $em->flush();
    }

    public function getLastRun(Job $job): array
    {
        $em = $this->getEntityManager();
        $qb = $this->createQueryBuilder('run');

        $lastrun = $qb
            ->where('run.job = :job')
            ->orderBy('run.timestamp', 'DESC')
            ->setParameter(':job', $job)
            ->getQuery()->getFirstResult();

        return $lastrun;
    }

    public function isSlowJob(Job $job, int $timelimit = 5): bool
    {
        $qb = $this->createQueryBuilder('run');

        $slowJob = $qb
            ->select('AVG(run.runtime) AS average')
            ->where('run.job = :job')
            ->setMaxResults(5)
            ->setParameter(':job', $job)
            ->getQuery()->getArrayResult();

        return $slowJob[0]['average'] > $timelimit;
    }

    public function cleanupRuns(array $jobids, int $maxage = NULL): int
    {
        $em = $this->getEntityManager();
        $jobRepo = $em->getRepository(Job::class);
        $allJobs = [];

        if(empty($jobids)) {
            $allJobs = $jobRepo->getAllJobs(true);
            foreach($allJobs as $key=>$job) {
                $jobids[] = $key;
            }
        } else {
            foreach($jobids as $jobid) {
                $job = $jobRepo->find($jobid);
                $allJobs[] = $job;
            }
        }
        $qb = $this->createQueryBuilder('run');
        $delete = $qb->delete();
        if($maxage == NULL) {
            foreach ($allJobs as $key=>$job) {
                $jobData = $job->getData();
                if(isset($jobData['retention']) && in_array($key, $jobids)) {
                    $delete = $delete
                        ->orWhere(
                            $qb->expr()->andX(
                                $qb->expr()->eq('run.job', ':job' . $key),
                                $qb->expr()->lt('run.timestamp', ':timestamp' . $key)
                            )
                        )
                        ->setParameter(':job' . $key, $job)
                        ->setParameter(':timestamp' . $key, time() - ($jobData['retention'] * 24 * 60 * 60));
                }
            }
        } else {
            if(!empty($jobids)) {
                $jobexpr = $qb->expr()->orX();
                foreach($allJobs as $key=>$job){
                    $jobexpr->add('run.job = :job' . $key);
                    $delete = $delete->setParameter(':job' . $key, $job);
                }
                $delete = $delete
                    ->where($jobexpr);
            }
            $delete = $delete
                ->andWhere('run.timestamp < :timestamp')
                ->setParameter(':timestamp', time() - ($maxage * 24 * 60 * 60));
        }
        return $delete->getQuery()->execute();
    }
}