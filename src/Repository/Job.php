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
        if(empty($values['crontype']) ||
            empty($values['name']) ||
            empty($values['interval']) ||
            empty($values['nextrun'])
        ) {
            throw new \InvalidArgumentException('Some fields are empty');
        }

        $data = $this->prepareJob($values);
        $data['data'] = json_encode($data['data']);
        $addJobSql = "INSERT INTO job(name, data, interval, nextrun, lastrun) VALUES (:name, :data, :interval, :nextrun, :lastrun)";

        $addJobStmt = $this->dbcon->prepare($addJobSql);
        $addJobStmt->executeQuery([':name' => $data['name'], ':data' => $data['data'], ':interval' => $data['interval'], ':nextrun' => $data['nextrun'], ':lastrun' => $data['lastrun'], ]);

        return ['success' => true, 'message' => 'Cronjob succesfully added'];
    }

    public function editJob(int $id, array $values)
    {
        if(empty($values['crontype']) ||
            empty($values['name']) ||
            empty($values['interval']) ||
            empty($values['nextrun'])
        ) {
            throw new \InvalidArgumentException('Some fields are empty');
        }

        $data = $this->prepareJob($values);
        $data['data'] = json_encode($data['data']);
        $editJobSql = "UPDATE job set name = :name, data = :data, interval = :interval, nextrun = :nextrun, lastrun = :lastrun WHERE id = :id";

        $editJobStmt = $this->dbcon->prepare($editJobSql);
        $editJobStmt->executeQuery([':name' => $data['name'], ':data' => $data['data'], ':interval' => $data['interval'], ':nextrun' => $data['nextrun'], ':lastrun' => $data['lastrun'],':id' => $id ]);

        return ['success' => true, 'message' => 'Cronjob succesfully edited'];
    }

    public function prepareJob(array $values): array
    {
        if(empty($values['lastrun'])) {
            $values['lastrun'] = NULL;
        } else {
            $values['lastrun'] = DateTime::createFromFormat('m/d/Y H:i:s',$values['lastrun'])->getTimestamp();
        }

        $values['nextrun'] = DateTime::createFromFormat('m/d/Y H:i:s', $values['nextrun'])->getTimestamp();
        $values['data']['crontype'] = $values['crontype'];
        $values['data']['hosttype'] = $values['hosttype'];
        $values['data']['containertype'] = $values['containertype'];

        switch($values['data']['crontype'])
        {
            case 'command':
                $values['data']['command'] = $values['command'];
                $values['data']['response'] = $values['response'];
                break;
            case 'reboot':
                $values['data']['reboot-command'] = $values['reboot-command'];
                $values['data']['getservices-command'] = $values['getservices-command'];
                $values['data']['reboot-duration'] = $values['reboot-duration'];
                if(!empty($values['reboot-delay'])) {
                    $newsecretkey = count($values['var-value']);
                    $values['var-id'][$newsecretkey] = 'reboot-delay';
                    $values['var-issecret'][$newsecretkey] = false;
                    $values['var-value'][$newsecretkey] = (int)$values['reboot-delay'];

                    $newsecretkey = count($values['var-value']);
                    $values['var-id'][$newsecretkey] = 'reboot-delay-secs';
                    $values['var-issecret'][$newsecretkey] = false;
                    $values['var-value'][$newsecretkey] = (int)$values['reboot-delay'] * 60;
                }
                break;
            case 'http':
                $parsedUrl = parse_url($values['url']);
                $values['data']['url'] = $values['url'];
                $values['data']['response'] = $values['response'];
                $values['data']['basicauth-username'] = $values['basicauth-username'];
                if(empty($parsedUrl['host'])) {
                    throw new \InvalidArgumentException('Some data was invalid');
                }
                if(!empty($values['basicauth-password'])) {
                    $newsecretkey = count($values['var-value']);
                    $values['var-id'][$newsecretkey] = 'basicauth-password';
                    $values['var-issecret'][$newsecretkey] = true;
                    $values['var-value'][$newsecretkey] = $values['basicauth-password'];
                }
                $values['data']['host'] = $parsedUrl['host'];
                break;
        }

        switch($values['data']['hosttype']) {
            case 'local':
                $values['data']['host'] = 'localhost';
                break;
            case 'ssh':
                $values['data']['host'] = $values['host'];
                $values['data']['user'] = $values['user'];
                if(!empty($values['privkey-password'])) {
                    $newsecretkey = count($values['var-value']);
                    $values['var-id'][$newsecretkey] = 'privkey-password';
                    $values['var-issecret'][$newsecretkey] = true;
                    $values['var-value'][$newsecretkey] = $values['privkey-password'];
                }
                if(!empty($_FILES['privkey']['tmp_name'])) {
                    $newsecretkey = count($values['var-value']);
                    $values['var-id'][$newsecretkey] = 'ssh-privkey';
                    $values['var-issecret'][$newsecretkey] = true;
                    $values['var-value'][$newsecretkey] = base64_encode(file_get_contents($_FILES['privkey']['tmp_name']));
                }
                break;
        }


        switch($values['data']['containertype']) {
            case 'docker':
                $values['data']['service'] = $values['service'];
                $values['data']['user'] = $values['user'];
                break;
        }

        if(!empty($values['var-value'])) {
            foreach($values['var-value'] as $key => $name) {
                if(!empty($name)) {
                    if(isset($values['var-issecret'][$key]) && $values['var-issecret'][$key] != false) {
                        $values['data']['vars'][$values['var-id'][$key]]['issecret'] = true;
                        $values['data']['vars'][$values['var-id'][$key]]['value'] = base64_encode(Secret::encrypt($values['var-value'][$key]));
                    } else {
                        $values['data']['vars'][$values['var-id'][$key]]['issecret'] = false;
                        $values['data']['vars'][$values['var-id'][$key]]['value'] = $values['var-value'][$key];
                    }
                }
            }
        }
        return $values;
    }

    public function getJob(int $id, bool $withSecrets = false) {
        $jobSql = "SELECT * FROM job WHERE id = :id";
        $jobStmt = $this->dbcon->prepare($jobSql);
        $jobRslt = $jobStmt->execute([':id' => $id])->fetchAssociative();

        $jobRslt['data'] = json_decode($jobRslt['data'], true);

        if(!empty($jobRslt['data']['vars'])) {
            foreach ($jobRslt['data']['vars'] as $key => &$value) {
                if ($value['issecret']) {
                    $value['value'] = ($withSecrets) ? Secret::decrypt(base64_decode($value['value'])) : '';
                }
            }
        }

        switch($jobRslt['data']['crontype']) {
            case 'http':
                if(isset($jobRslt['data']['vars']['basicauth-password']['value'])) {
                    $jobRslt['data']['basicauth-password'] = $jobRslt['data']['vars']['basicauth-password']['value'];
                    unset($jobRslt['data']['vars']['basicauth-password']);
                }
                break;
        }
        if($jobRslt['data']['crontype'] == 'http') {
        }
        return $jobRslt;
    }
}