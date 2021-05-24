<?php


namespace JeroenED\Webcron\Repository;


use DateTime;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

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
        $jobsRslt = $jobsStmt->executeQuery();
        $jobs = $jobsRslt->fetchAllAssociative();
        foreach ($jobs as $key=>&$job) {
            $job['data'] = json_decode($job['data'], true);
        }
        return $jobs;
    }


    public function getJobsDue()
    {
        $jobsSql = "SELECT id FROM job WHERE nextrun <= :timestamp AND running = 0";
        $jobsStmt = $this->dbcon->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery([':timestamp' => time()]);
        $jobs = $jobsRslt->fetchAllAssociative();
        $return = [];
        foreach ($jobs as $job) {
            $return[] = $job['id'];
        }
        return $return;
    }

    public function setJobRunning(int $job, bool $status): void
    {
        $jobsSql = "UPDATE job SET running = :status WHERE id = :id";
        $jobsStmt = $this->dbcon->prepare($jobsSql);
        $jobsStmt->executeQuery([':id' => $job, ':status' => $status ? 1 : 0]);
        return;
    }

    public function runJob(int $job)
    {
        $job = $this->getJob($job, true);
        if($job['data']['crontype'] == 'http') {

            $client = new Client();
            foreach($job['data']['vars'] as $key => $var) {
                $job['data']['basicauth-username'] = str_replace('{' . $key . '}', $var['value'], $job['data']['basicauth-username']);
                $job['data']['url'] = str_replace('{' . $key . '}', $var['value'], $job['data']['url']);
            }

            $url = $job['data']['url'];
            $options['http_errors'] = false;
            $options['auth'] = [$job['data']['basicauth-username'], $job['data']['basicauth-password']];
            $res = $client->request('GET', $url, $options);

            $exitcode = $res->getStatusCode();
            $output = $res->getBody();
        } elseif($job['data']['crontype'] == 'command') {
            if($job['data']['hosttype'] == 'ssh') {
                $ssh = new SSH2($job['data']['host']);
                $key = null;
                if(!empty($job['data']['ssh-privkey'])) {
                    if(!empty($job['data']['privkey-password'])) {
                        $key = PublicKeyLoader::load(base64_decode($job['data']['ssh-privkey']), $job['data']['privkey-password']);
                    } else {
                        $key = PublicKeyLoader::load(base64_decode($job['data']['ssh-privkey']));
                    }
                } elseif (!empty($job['data']['privkey-password'])) {
                    $key = $job['data']['ssh-privkey'];
                }

                if (!$ssh->login($job['data']['user'], $key)) {
                    throw new \Exception('Login failed');
                }
                $output = $ssh->exec($job['data']['command']);
                $exitcode = $ssh->getExitStatus();
            }
        }

        // handling of response
        $addRunSql = 'INSERT INTO run(job_id, exitcode, output) VALUES (:job_id, :exitcode, :output)';
        $addRunStmt = $this->dbcon->prepare($addRunSql);
        $addRunStmt->executeQuery([':job_id' => $job['id'], ':exitcode' => $exitcode, ':output' => $output]);

        // setting nextrun to next run
        $nextrun = $job['nextrun'];
        do {
            $nextrun = $nextrun + $job['interval'];
        } while ($nextrun < time());


        $addRunSql = 'UPDATE job SET nextrun = :nextrun, timestamp = :timestamp WHERE id = :id';
        $addRunStmt = $this->dbcon->prepare($addRunSql);
        $addRunStmt->executeQuery([':id' => $job['id'], ':nextrun' => $nextrun, ':timestamp' => time()]);
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
        $addJobSql = "INSERT INTO job(name, data, interval, nextrun, lastrun, running) VALUES (:name, :data, :interval, :nextrun, :lastrun, :running)";

        $addJobStmt = $this->dbcon->prepare($addJobSql);
        $addJobStmt->executeQuery([':name' => $data['name'], ':data' => $data['data'], ':interval' => $data['interval'], ':nextrun' => $data['nextrun'], ':lastrun' => $data['lastrun'], ':running' => 0]);

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
            $values['lastrun'] = DateTime::createFromFormat('d/m/Y H:i:s',$values['lastrun'])->getTimestamp();
        }

        $values['nextrun'] = DateTime::createFromFormat('d/m/Y H:i:s', $values['nextrun'])->getTimestamp();
        $values['data']['crontype'] = $values['crontype'];
        $values['data']['hosttype'] = $values['hosttype'];
        $values['data']['containertype'] = $values['containertype'];

        if(empty($values['data']['crontype'])) {
            throw new \InvalidArgumentException("Crontype cannot be empty");
        }
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
            default:
                $values['data']['hosttype'] = $values['data']['crontype'] == 'http' ? '' : 'local';
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
                $privkeyid = NULL;
                if(!empty($_FILES['privkey']['tmp_name'])) {
                    $newsecretkey = count($values['var-value']);
                    $privkeyid = $newsecretkey;
                    $values['var-id'][$newsecretkey] = 'ssh-privkey';
                    $values['var-issecret'][$newsecretkey] = true;
                    $values['var-value'][$newsecretkey] = base64_encode(file_get_contents($_FILES['privkey']['tmp_name']));
                }
                if($values['privkey-keep'] == true) {
                    $privkeyid = ($privkeyid === NULL) ? count($values['var-value']) : $privkeyid ;
                    $values['var-id'][$privkeyid] = 'ssh-privkey';
                    $values['var-issecret'][$privkeyid] = true;
                    $values['var-value'][$privkeyid] = $values['privkey-orig'];

                }
                break;
        }


        switch($values['data']['containertype']) {
            default:
                $values['data']['containertype'] = $values['data']['crontype'] == 'http' ? '' :'none';
            case 'none':
                // No options for no container
                break;
            case 'docker':
                $values['data']['service'] = $values['service'];
                $values['data']['container-user'] = $values['container-user'];
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
            case 'reboot':
                $jobRslt['data']['reboot-delay'] = $jobRslt['data']['vars']['reboot-delay']['value'];
                unset($jobRslt['data']['vars']['reboot-delay']);
                unset($jobRslt['data']['vars']['reboot-delay-secs']);
                break;
        }

        switch($jobRslt['data']['hosttype']) {
            case 'ssh':
                if(isset($jobRslt['data']['vars']['ssh-privkey']['value'])) {
                    $jobRslt['data']['ssh-privkey'] = $jobRslt['data']['vars']['ssh-privkey']['value'];
                    unset($jobRslt['data']['vars']['ssh-privkey']);
                }
                if(isset($jobRslt['data']['vars']['privkey-password']['value'])) {
                    $jobRslt['data']['privkey-password'] = $jobRslt['data']['vars']['privkey-password']['value'];
                    unset($jobRslt['data']['vars']['privkey-password']);
                }
                break;
        }
        if($jobRslt['data']['crontype'] == 'http') {
        }
        return $jobRslt;
    }

    public function deleteJob(int $id)
    {
        $addJobSql = "DELETE FROM job WHERE id = :id";

        $addJobStmt = $this->dbcon->prepare($addJobSql);
        $addJobStmt->executeQuery([':id' => $id]);

        return ['success' => true, 'message' => 'Cronjob succesfully deleted'];
    }
}