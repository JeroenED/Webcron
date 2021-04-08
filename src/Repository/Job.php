<?php


namespace JeroenED\Webcron\Repository;


use Doctrine\DBAL\Connection;

class Job
{
    private Connection $dbcon;

    public function __construct(Connection $dbcon)
    {
        $this->dbcon = $dbcon;
    }

    public function getAllJobs()
    {
        $jobsSql = "SELECT * FROM job";
        $jobsStmt = $this->dbcon->prepare($jobsSql);
        $jobsRslt = $jobsStmt->execute();
        $jobs = $jobsRslt->fetchAllAssociative();
        foreach ($jobs as $key=>&$job) {
            $job['data'] = json_decode($job['data'], true);
        }
        return $jobs;
    }
}