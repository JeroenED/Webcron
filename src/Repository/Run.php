<?php


namespace JeroenED\Webcron\Repository;


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
}