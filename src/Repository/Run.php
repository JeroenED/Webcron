<?php


namespace JeroenED\Webcron\Repository;


use Doctrine\DBAL\Exception;
use JeroenED\Framework\Repository;

class Run extends Repository
{
    const FAILED = 'F';
    const SUCCESS = 'S';
    const MANUAL = 'M';

    public function getRunsForJob(int $id, $failed = true, $ordered = true): array
    {
        $runsSql = "SELECT * FROM run WHERE job_id = :job";
        $params = [':job' => $id];
        if ($failed) {
            $runsSql .= ' AND flags LIKE "%' . Run::FAILED . '%"';
        }
        if ($ordered) $runsSql .= ' ORDER by timestamp DESC';
        $runsStmt = $this->dbcon->prepare($runsSql);
        $runsRslt = $runsStmt->executeQuery($params);
        $runs = $runsRslt->fetchAllAssociative();
        return $runs;
    }

    public function addRun(int $jobid, string $exitcode, int $starttime, float $runtime, string $output, array $flags): void
    {
        // handling of response
        $addRunSql = 'INSERT INTO run(job_id, exitcode, output, runtime, timestamp,flags) VALUES (:job_id, :exitcode, :output, :runtime, :timestamp, :flags)';
        $addRunStmt = $this->dbcon->prepare($addRunSql);
        $addRunStmt->executeQuery([':job_id' => $jobid, ':exitcode' => $exitcode, 'output' => $output, 'runtime' => $runtime, ':timestamp' => $starttime, ':flags' => implode("", $flags)]);
    }

    public function getLastRun(int $jobid): array
    {
        $lastRunSql = 'SELECT * FROM run WHERE job_id = :jobid ORDER BY timestamp DESC LIMIT 1';
        $lastRun = $this->dbcon->prepare($lastRunSql)->executeQuery([':jobid' => $jobid])->fetchAssociative();
        return $lastRun;
    }

    public function isSlowJob(int $jobid, int $timelimit = 5): bool
    {
        $slowJobSql = 'SELECT AVG(runtime) as average FROM run WHERE job_id = :jobid LIMIT 5';
        $slowJob = $this->dbcon->prepare($slowJobSql)->executeQuery([':jobid' => $jobid])->fetchAssociative();
        return $slowJob['average'] > $timelimit;
    }

    public function cleanupRuns(array $jobids, int $maxage): int
    {
        $sql = 'DELETE FROM run WHERE timestamp < :timestamp';
        $params[':timestamp'] = time() - ($maxage * 24 * 60 * 60);
        if(!empty($jobids)) {
            $jobidsql = [];
            foreach($jobids as $key=>$jobid){
                $jobidsql[] = ':job' . $key;
                $params[':job' . $key] = $jobid;
            }
            $sql .= ' AND job_id in (' . implode(',', $jobidsql) . ')';
        }
        try {
            return $this->dbcon->prepare($sql)->executeQuery($params)->rowCount();
        } catch(Exception $exception) {
            throw $exception;
        }
    }
}