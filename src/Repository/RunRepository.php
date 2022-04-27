<?php


namespace App\Repository;


use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityRepository;

class RunRepository extends EntityRepository
{
    const FAILED = 'F';
    const SUCCESS = 'S';
    const MANUAL = 'M';

    public function getRunsForJob(int $id, bool $onlyfailed = false, int $maxage = NULL, bool $ordered = true): array
    {
        $runsSql = "SELECT * FROM run WHERE job_id = :job";
        $params = [':job' => $id];
        if ($onlyfailed) {
            $runsSql .= ' AND flags LIKE "%' . RunRepository::FAILED . '%"';
        }
        if($maxage !== NULL) {
            $runsSql .= ' AND timestamp > :timestamp';
            $params[':timestamp'] = time() - ($maxage * 24 * 60 * 60);
        }
        if ($ordered) $runsSql .= ' ORDER by timestamp DESC';
        $runsStmt = $this->getEntityManager()->getConnection()->prepare($runsSql);
        $runsRslt = $runsStmt->executeQuery($params);
        $runs = $runsRslt->fetchAllAssociative();
        return $runs;
    }

    public function addRun(int $jobid, string $exitcode, int $starttime, float $runtime, string $output, array $flags): void
    {
        // handling of response
        $addRunSql = 'INSERT INTO run(job_id, exitcode, output, runtime, timestamp,flags) VALUES (:job_id, :exitcode, :output, :runtime, :timestamp, :flags)';
        $addRunStmt = $this->getEntityManager()->getConnection()->prepare($addRunSql);
        $addRunStmt->executeQuery([':job_id' => $jobid, ':exitcode' => $exitcode, 'output' => $output, 'runtime' => $runtime, ':timestamp' => $starttime, ':flags' => implode("", $flags)]);
    }

    public function getLastRun(int $jobid): array
    {
        $lastRunSql = 'SELECT * FROM run WHERE job_id = :jobid ORDER BY timestamp DESC LIMIT 1';
        $lastRun = $this->getEntityManager()->getConnection()->prepare($lastRunSql)->executeQuery([':jobid' => $jobid])->fetchAssociative();
        return $lastRun;
    }

    public function isSlowJob(int $jobid, int $timelimit = 5): bool
    {
        $slowJobSql = 'SELECT AVG(runtime) as average FROM run WHERE job_id = :jobid LIMIT 5';
        $slowJob = $this->getEntityManager()->getConnection()->prepare($slowJobSql)->executeQuery([':jobid' => $jobid])->fetchAssociative();
        return $slowJob['average'] > $timelimit;
    }

    public function cleanupRuns(array $jobids, int $maxage = NULL): int
    {
        $jobRepo = new JobRepository($this->dbcon);
        $allJobs = $jobRepo->getAllJobs(true);
        if(empty($jobids)) {
            foreach($allJobs as $key=>$job) {
                $jobids[] = $key;
            }
        }
        $sqldelete = [];
        if($maxage == NULL) {
            foreach ($allJobs as $key=>$job) {
                if(isset($job['data']['retention']) && in_array($key, $jobids)) {
                    $sqldelete[] = '( job_id = :job' . $key . ' AND timestamp < :timestamp' . $key . ')';
                    $params[':job' . $key] = $key;
                    $params[':timestamp' . $key] = time() - ($job['data']['retention'] * 24 * 60 * 60);
                }
            }
        } else {
            $sqljobids = '';
            if(!empty($jobids)) {
                $jobidsql = [];
                foreach($jobids as $key=>$jobid){
                    $jobidsql[] = ':job' . $key;
                    $params[':job' . $key] = $jobid;
                }
                $sqljobids = ' AND job_id in (' . implode(',', $jobidsql) . ')';
            }
            $params[':timestamp'] = time() - ($maxage * 24 * 60 * 60);
            $sqldelete[] = 'timestamp < :timestamp' . $sqljobids;
        }
        $sql = 'DELETE FROM run WHERE ' . implode(' OR ', $sqldelete);
        try {
            return $this->getEntityManager()->getConnection()->prepare($sql)->executeStatement($params);
        } catch(Exception $exception) {
            throw $exception;
        }
    }
}