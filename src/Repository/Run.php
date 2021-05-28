<?php


namespace JeroenED\Webcron\Repository;


use JeroenED\Framework\Repository;

class Run extends Repository
{
    public function getRunsForJob(int $id, $excludedexitcodes = [], $ordered = true): array
    {
        $runsSql = "SELECT * FROM run WHERE job_id = :job";
        $params = [':job' => $id];
        if (!empty($excludedexitcodes)) {
            $runsSql .= ' AND exitcode NOT in ';
            $exitcodes = [];
            foreach($excludedexitcodes as $key => $exitcode) {
                $exitcodes[] = ':code' . $key;
                $params[':code' . $key] = $exitcode;
            }
            $runsSql .= '(' . implode(',', $exitcodes) . ')';
        }
        if ($ordered) $runsSql .= ' ORDER by timestamp DESC';
        $runsStmt = $this->dbcon->prepare($runsSql);
        $runsRslt = $runsStmt->executeQuery($params);
        $runs = $runsRslt->fetchAllAssociative();
        return $runs;
    }
}