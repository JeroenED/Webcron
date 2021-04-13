<?php


namespace JeroenED\Webcron\Repository;


use DateTime;
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

    public function addJob(array $values)
    {
        if(empty($values['type']) ||
            empty($values['name']) ||
            empty($values['delay']) ||
            empty($values['nextrun'])
        ) {
            return ['success' => false, 'message' => 'Some fields are empty'];
        }

        if(empty($values['lastrun'])) {
            $values['lastrun'] = NULL;
        } else {
            $values['lastrun'] = DateTime::createFromFormat('m/d/Y g:i:s A',$values['lastrun'])->getTimestamp();
        }

        $values['nextrun'] = DateTime::createFromFormat('m/d/Y g:i:s A', $values['nextrun'])->getTimestamp();
        $data['type'] = $values['type'];

        switch($data['type'])
        {
            case 'http':
                $parsedUrl = parse_url($values['url']);
                $data['url'] = $values['url'];
                $data['basicauth-username'] = $values['basicauth-username'];
                if(empty($parsedUrl['host'])) {
                    return ['success' => false, 'message' => 'Some data was invalid'];
                }
                $data['host'] = $parsedUrl['host'];
                break;
        }

        if(!empty($values['secretval'])) {
            foreach($values['secretval'] as $key => $name) {
                if(!empty($name)) $data['secrets'][$values['secretid'][$key]] = base64_encode(Secret::encrypt($values['secretval'][$key]));
            }
        }

        $data = json_encode($data);
        $addJobSql = "INSERT INTO job(name, data, delay, nextrun, lastrun) VALUES (:name, :data, :delay, :nextrun, :lastrun)";

        $addJobStmt = $this->dbcon->prepare($addJobSql);
        $addJobStmt->execute([':name' => $values['name'], ':data' => $data, ':delay' => $values['delay'], ':nextrun' => $values['nextrun'], ':lastrun' => $values['lastrun'], ]);

        return ['success' => true, 'message' => 'Cronjob succesfully added'];
    }

    public function getJob(int $id, bool $withSecrets = false) {
        $jobSql = "SELECT * FROM job WHERE id = :id";
        $jobStmt = $this->dbcon->prepare($jobSql);
        $jobRslt = $jobStmt->execute([':id' => $id])->fetchAssociative();

        $jobRslt['data'] = json_decode($jobRslt['data'], true);
        if(!empty($jobRslt['data']['secrets'])) {
            foreach ($jobRslt['data']['secrets'] as $key => &$value) {
                $value = ($withSecrets) ? Secret::decrypt(base64_decode($value)) : '';
            }
        }

        return $jobRslt;
    }
}