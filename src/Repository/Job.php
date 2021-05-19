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
            return ['success' => false, 'message' => 'Some fields are empty'];
        }

        if(empty($values['lastrun'])) {
            $values['lastrun'] = NULL;
        } else {
            $values['lastrun'] = DateTime::createFromFormat('m/d/Y g:i:s A',$values['lastrun'])->getTimestamp();
        }

        $values['nextrun'] = DateTime::createFromFormat('m/d/Y g:i:s A', $values['nextrun'])->getTimestamp();
        $data['crontype'] = $values['crontype'];
        $data['hosttype'] = $values['hosttype'];

        switch($data['crontype'])
        {
            case 'command':
                $data['command'] = $values['command'];
                break;
            case 'reboot':
                $data['reboot-command'] = $values['reboot-command'];
                $data['getservices-command'] = $values['getservices-command'];
                $data['reboot-duration'] = $values['reboot-duration'];
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
                $data['url'] = $values['url'];
                $data['basicauth-username'] = $values['basicauth-username'];
                if(empty($parsedUrl['host'])) {
                    return ['success' => false, 'message' => 'Some data was invalid'];
                }
                if(!empty($values['basicauth-password'])) {
                    $newsecretkey = count($values['var-value']);
                    $values['var-id'][$newsecretkey] = 'basicauth-password';
                    $values['var-issecret'][$newsecretkey] = true;
                    $values['var-value'][$newsecretkey] = $values['basicauth-password'];
                }
                $data['host'] = $parsedUrl['host'];
                break;
        }

        switch($data['hosttype']) {
            case 'local':
                $data['host'] = 'localhost';
                break;
            case 'ssh':
                $data['host'] = $values['host'];
                $data['user'] = $values['user'];
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

        if(!empty($values['var-value'])) {
            foreach($values['var-value'] as $key => $name) {
                if(!empty($name)) {
                    if(isset($values['var-issecret'][$key]) && $values['var-issecret'][$key] != false) {
                        $data['vars'][$values['var-id'][$key]]['issecret'] = true;
                        $data['vars'][$values['var-id'][$key]]['value'] = base64_encode(Secret::encrypt($values['var-value'][$key]));
                    } else {
                        $data['vars'][$values['var-id'][$key]]['issecret'] = false;
                        $data['vars'][$values['var-id'][$key]]['value'] = $values['var-value'][$key];
                    }
                }
            }
        }

        $data = json_encode($data);
        $addJobSql = "INSERT INTO job(name, data, interval, nextrun, lastrun) VALUES (:name, :data, :interval, :nextrun, :lastrun)";

        $addJobStmt = $this->dbcon->prepare($addJobSql);
        $addJobStmt->executeQuery([':name' => $values['name'], ':data' => $data, ':interval' => $values['interval'], ':nextrun' => $values['nextrun'], ':lastrun' => $values['lastrun'], ]);

        return ['success' => true, 'message' => 'Cronjob succesfully added'];
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

        return $jobRslt;
    }
}