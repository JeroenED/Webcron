<?php


namespace App\Repository;


use App\Entity\Run;
use App\Service\Secret;
use DateTime;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class JobRepository extends EntityRepository
{
    public function getFailingJobs()
    {
        $runRepo = $this->getEntityManager()->getRepository(Run::class);

        $jobsSql = "SELECT * FROM job";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery();
        $jobs = $jobsRslt->fetchAllAssociative();
        foreach ($jobs as $key=>&$job) {
            $job['data'] = json_decode($job['data'], true);
            $job['host-displayname'] = $job['data']['host'];
            $job['host'] = $job['data']['host'];
            $job['service'] = $job['data']['service'] ?? '';
            $failedruns = $runRepo->getRunsForJob($job['id'], true, $job['data']['fail-days']);
            $failed = count($failedruns);
            $all = count($runRepo->getRunsForJob($job['id'], false, $job['data']['fail-days']));
            $job['lastfail'] = $failedruns[0] ?? NULL;
            $job['needschecking'] = $all > 0  && (($failed / $all) * 100) > $job['data']['fail-pct'];
            if(!empty($job['data']['containertype']) && $job['data']['containertype'] != 'none') {
                $job['host-displayname'] = $job['data']['service'] . ' on ' . $job['data']['host'];
            }
            if($job['needschecking']) $failingjobs[] = $job;
        }

        if(empty($failingjobs)) return [];
        array_multisort(
            array_column($failingjobs, 'name'), SORT_ASC,
            array_column($failingjobs, 'host'), SORT_ASC,
            array_column($failingjobs, 'service'), SORT_ASC,
            $failingjobs);
        return $failingjobs;
    }

    public function getRunningJobs(bool $idiskey = false)
    {
        $runRepo = $this->getEntityManager()->getRepository(Run::class);

        $jobsSql = "SELECT * FROM job WHERE running != 0;";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery();
        $jobs = $jobsRslt->fetchAllAssociative();
        $returnbyid = [];
        foreach ($jobs as $key=>&$job) {
            $job['data'] = json_decode($job['data'], true);
            $job['host-displayname'] = $job['data']['host'];
            $job['host'] = $job['data']['host'];
            $job['service'] = $job['data']['service'] ?? '';
            $job['norun'] = isset($job['lastrun']) && $job['nextrun'] > $job['lastrun'];
            $job['running'] = $job['running'] != 0;
            $failed = count($runRepo->getRunsForJob($job['id'], true, $job['data']['fail-days']));
            $all = count($runRepo->getRunsForJob($job['id'], false, $job['data']['fail-days']));
            $job['needschecking'] = $all > 0  && (($failed / $all) * 100) > $job['data']['fail-pct'];
            if(!empty($job['data']['containertype']) && $job['data']['containertype'] != 'none') {
                $job['host-displayname'] = $job['data']['service'] . ' on ' . $job['data']['host'];
            }
            if($idiskey) $returnbyid[$job['id']] = $job;
        }

        if($idiskey) return $returnbyid;
        array_multisort(
            array_column($jobs, 'name'), SORT_ASC,
            array_column($jobs, 'host'), SORT_ASC,
            array_column($jobs, 'service'), SORT_ASC,
            $jobs);
        return $jobs;
    }

    public function getAllJobs(bool $idiskey = false)
    {
        $runRepo = $this->getEntityManager()->getRepository(Run::class);
        $jobsSql = "SELECT * FROM job";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery();
        $jobs = $jobsRslt->fetchAllAssociative();
        $returnbyid = [];
        foreach ($jobs as $key=>&$job) {
            $job['data'] = json_decode($job['data'], true);
            $job['host-displayname'] = $job['data']['host'];
            $job['host'] = $job['data']['host'];
            $job['service'] = $job['data']['service'] ?? '';
            $job['norun'] = isset($job['lastrun']) && $job['nextrun'] > $job['lastrun'];
            $job['running'] = $job['running'] != 0;
            $failed = count($runRepo->getRunsForJob($job['id'], true, $job['data']['fail-days']));
            $all = count($runRepo->getRunsForJob($job['id'], false, $job['data']['fail-days']));
            $job['needschecking'] = $all > 0  && (($failed / $all) * 100) > $job['data']['fail-pct'];
            if(!empty($job['data']['containertype']) && $job['data']['containertype'] != 'none') {
                $job['host-displayname'] = $job['data']['service'] . ' on ' . $job['data']['host'];
            }
            if($idiskey) $returnbyid[$job['id']] = $job;
        }

        if($idiskey) return $returnbyid;
        array_multisort(
            array_column($jobs, 'name'), SORT_ASC,
            array_column($jobs, 'host'), SORT_ASC,
            array_column($jobs, 'service'), SORT_ASC,
            $jobs);
        return $jobs;
    }

    public function getErrorRatio(int $jobId): bool
    {
        $errorSql = "SELECT count(*) as count FROM job WHERE id = :id";
        $errorStmt = $this->getEntityManager()->getConnection()->prepare($errorSql);
        $errorRslt = $errorStmt->executeQuery([':timestamp' => time(), ':timestamplastrun' => time(), ':timestamprun' => time()]);
        $error = $errorRslt->fetchAllAssociative();

        $errorSql = "SELECT count(*) as count FROM job WHERE id = :id";
        $errorStmt = $this->getEntityManager()->getConnection()->prepare($errorSql);
        $errorRslt = $errorStmt->executeQuery([':timestamp' => time(), ':timestamplastrun' => time(), ':timestamprun' => time()]);
        $error = $errorRslt->fetchAllAssociative();
    }

    public function getJobsDue()
    {
        $jobsSql = "SELECT id, running
                    FROM job
                    WHERE (
                        nextrun <= :timestamp
                        AND (lastrun IS NULL OR lastrun > :timestamplastrun)
                        AND running IN (0,2)
                    )
                    OR (running NOT IN (0,1,2) AND running < :timestamprun)
                    OR (running = 2)";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery([':timestamp' => time(), ':timestamplastrun' => time(), ':timestamprun' => time()]);
        $jobs = $jobsRslt->fetchAllAssociative();
        return $jobs;
    }

    public function getTimeOfNextRun()
    {
        $jobsSql = "SELECT nextrun
                    FROM job
                    WHERE running = 0 and nextrun != :time
                    ORDER BY nextrun
                    LIMIT 1";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery([':time' => time()]);
        $nextjob = $jobsRslt->fetchAssociative();


        $jobsSql = "SELECT nextrun
                    FROM job
                    WHERE running = 2
                    ORDER BY nextrun
                    LIMIT 1";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery();
        $manualjob = $jobsRslt->fetchAssociative();

        if($nextjob == false && $manualjob == false) {
            return PHP_INT_MAX;
        }

        if($manualjob != false) {
            return 100;
        }


        $jobsSql = "SELECT running
                    FROM job
                    WHERE running > 2
                    ORDER BY nextrun DESC
                    LIMIT 1";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsRslt = $jobsStmt->executeQuery();
        $running = $jobsRslt->fetchAssociative();

        if($running == false) {
            return (int)$nextjob['nextrun'];
        }

        return $nextjob < $running ? (int)$running ['running']: (int)$nextjob['nextrun'];
    }

    public function setJobRunning(int $job, bool $status): void
    {
        $jobsSql = "UPDATE job SET running = :status WHERE id = :id AND running IN (0,1,2)";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsStmt->executeQuery([':id' => $job, ':status' => $status ? 1 : 0]);
        return;
    }

    public function setTempVar(int $job, string $name, mixed $value): void
    {
        $jobsSql = "SELECT data FROM job WHERE id = :id";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $result = $jobsStmt->executeQuery([':id' => $job])->fetchAssociative();
        $result = json_decode($result['data'], true);
        $result['temp_vars'][$name] = $value;


        $jobsSql = "UPDATE  job SET data = :data WHERE id = :id";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsStmt->executeQuery([':id' => $job, ':data' => json_encode($result)]);
        return;
    }

    public function deleteTempVar(int $job, string $name): void
    {
        $jobsSql = "SELECT data FROM job WHERE id = :id";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $result = $jobsStmt->executeQuery([':id' => $job])->fetchAssociative();
        $result = json_decode($result['data'], true);
        unset($result['temp_vars'][$name]);

        $jobsSql = "UPDATE  job SET data = :data WHERE id = :id";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsStmt->executeQuery([':id' => $job, ':data' => json_encode($result)]);
        return;
    }

    public function getTempVar(int $job, string $name, mixed $default = NULL): mixed
    {
        $jobsSql = "SELECT data FROM job WHERE id = :id";
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $result = $jobsStmt->executeQuery([':id' => $job])->fetchAssociative();
        $result = json_decode($result['data'], true);
        return $result['temp_vars'][$name] ?? $default;
    }

    private function runHttpJob(array $job): array
    {
        $client = new Client();

        if(!empty($job['data']['vars'])) {
            foreach($job['data']['vars'] as $key => $var) {
                if (!empty($job['data']['basicauth-username'])) $job['data']['basicauth-username'] = str_replace('{' . $key . '}', $var['value'], $job['data']['basicauth-username']);
                $job['data']['url'] = str_replace('{' . $key . '}', $var['value'], $job['data']['url']);
            }
        }

        $url = $job['data']['url'];
        $options['http_errors'] = false;
        $options['auth'] = !empty($job['data']['basicauth-username']) ? [$job['data']['basicauth-username'], $job['data']['basicauth-password']] : NULL;
        try {
            $res = $client->request('GET', $url, $options);
            $return['exitcode'] = $res->getStatusCode();
            $return['output'] = $res->getBody();
            $return['failed'] = !in_array($return['exitcode'], $job['data']['http-status']);
        } catch(GuzzleException $exception) {
            $return['exitcode'] = $exception->getCode();
            $return['output'] = $exception->getMessage();
            $return['failed'] = true;
        }

        return $return;
    }

    private function runCommandJob(array $job): array
    {
        if(!empty($job['data']['vars'])) {
            foreach ($job['data']['vars'] as $key => $var) {
                $job['data']['command'] = str_replace('{' . $key . '}', $var['value'], $job['data']['command']);
            }
        }

        $command = $job['data']['command'];
        if ($job['data']['containertype'] == 'docker') {
            $command = $this->prepareDockerCommand($command, $job['data']['service'], $job['data']['container-user']);
        }
        try {
            if($job['data']['hosttype'] == 'local') {
                $return = $this->runLocalCommand($command);
            } elseif($job['data']['hosttype'] == 'ssh') {
                $return = $this->runSshCommand($command, $job['data']['host'], $job['data']['user'], $job['data']['ssh-privkey'], $job['data']['privkey-password']);
            }
            $return['failed'] = !in_array($return['exitcode'], $job['data']['response']);
        } catch (\RuntimeException $exception) {
            $return['exitcode'] = $exception->getCode();
            $return['output'] = $exception->getMessage();
            $return['failed'] = true;
        }

        return $return;
    }

    private function runLocalCommand(string $command): array
    {
        if(function_exists('pcntl_signal')) pcntl_signal(SIGCHLD, SIG_DFL);
        $return['exitcode'] = NULL;
        $return['output'] = NULL;
        exec($command . ' 2>&1', $return['output'], $return['exitcode']);
        if(function_exists('pcntl_signal'))pcntl_signal(SIGCHLD, SIG_IGN);
        $return['output'] = implode("\n", $return['output']);
        return $return;
    }

    private function runSshCommand(string $command, string $host, string $user, string $privkey, string $password): array
    {
        $ssh = new SSH2($host);
        $key = null;
        if(!empty($privkey)) {
            if(!empty($password)) {
                $key = PublicKeyLoader::load(base64_decode($privkey), $password);
            } else {
                $key = PublicKeyLoader::load(base64_decode($privkey));
            }
        } elseif (!empty($password)) {
            $key = $password;
        }
        if (!$ssh->login($user, $key)) {
            $return['output'] = "Login failed";
            $return['exitcode'] = 255;
            return $return;
        }
        $ssh->setTimeout(0);
        $return['output'] = $ssh->exec($command);
        $return['exitcode'] = $ssh->getExitStatus();
        $return['exitcode'] = (empty($return['exitcode'])) ? 0 : $return['exitcode'];
        return $return;
    }

    private function runRebootJob(array $job, float &$starttime, bool &$manual): array
    {
        if($job['running'] == 1) {
            $this->setTempVar($job['id'], 'starttime', $starttime);
            $this->setTempVar($job['id'], 'manual', $manual);
            $job['data']['reboot-command'] = str_replace('{reboot-delay}', $job['data']['reboot-delay'], $job['data']['reboot-command']);
            $job['data']['reboot-command'] = str_replace('{reboot-delay-secs}', $job['data']['reboot-delay-secs'], $job['data']['reboot-command']);

            if (!empty($job['data']['vars'])) {
                foreach ($job['data']['vars'] as $key => $var) {
                    $job['data']['reboot-command'] = str_replace('{' . $key . '}', $var['value'], $job['data']['reboot-command']);
                }
            }

            $jobsSql = "UPDATE job SET running = :status WHERE id = :id";
            $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
            $jobsStmt->executeQuery([':id' => $job['id'], ':status' => time() + $job['data']['reboot-delay-secs'] + ($job['data']['reboot-duration'] * 60)]);

            try {
                if($job['data']['hosttype'] == 'local') {
                    $this->runLocalCommand($job['data']['reboot-command']);
                } elseif($job['data']['hosttype'] == 'ssh') {
                    $this->runSshCommand($job['data']['reboot-command'], $job['data']['host'], $job['data']['user'], $job['data']['ssh-privkey'] ?? '', $job['data']['privkey-password'] ?? '');
                }
            } catch (\RuntimeException $exception) {
                $return['exitcode'] = $exception->getCode();
                $return['output'] = $exception->getMessage();
                $return['failed'] = true;
                return $return;
            }
            return ['status' => 'deferred'];

        } elseif($job['running'] != 0) {
            if($job['running'] > time()) {
                return ['status' => 'deferred'];
            }
            $starttime = (float)$this->getTempVar($job['id'], 'starttime');
            $this->deleteTempVar($job['id'], 'starttime');
            $manual = $this->getTempVar($job['id'], 'manual');
            $this->deleteTempVar($job['id'], 'manual');

            $jobsSql = "UPDATE job SET running = :status WHERE id = :id";
            $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
            $jobsStmt->executeQuery([':id' => $job['id'], ':status' => 1]);

            if (!empty($job['data']['vars'])) {
                foreach ($job['data']['vars'] as $key => $var) {
                    $job['data']['getservices-command'] = str_replace('{' . $key . '}', $var['value'], $job['data']['getservices-command']);
                }
            }
            try {
                if($job['data']['hosttype'] == 'local') {
                    $return = $this->runLocalCommand($job['data']['getservices-command']);
                } elseif($job['data']['hosttype'] == 'ssh') {
                    $return = $this->runSshCommand($job['data']['getservices-command'], $job['data']['host'], $job['data']['user'], $job['data']['ssh-privkey'] ?? '', $job['data']['privkey-password'] ?? '');
                }
            } catch (\RuntimeException $exception) {
                $return['exitcode'] = $exception->getCode();
                $return['output'] = $exception->getMessage();
                $return['failed'] = true;
                return $return;
            }
            $return['failed'] = !in_array($return['exitcode'], $job['data']['getservices-response']);
            return $return;
        }
    }

    public function runNow($job, $console = false) {
        $job = $this->getJob($job, true);
        $runRepo = $this->getEntityManager()->getRepository(Run::class);

        if($console == false && ($runRepo->isSlowJob($job['id']) || count($runRepo->getRunsForJob($job['id'])) == 0 || $job['data']['crontype'] === 'reboot')) {
            $jobsSql = "UPDATE job SET running = :status WHERE id = :id AND running IN (0,1,2)";
            $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
            $jobsStmt->executeQuery([':id' => $job['id'], ':status' => 2]);
        } else {
            $output = $this->runJob($job['id'], true);
            if(!(isset($output['status']) && $output['status'] == 'deferred'))
            return [
                'status' => 'ran',
                'output' => ($console) ? $output['output'] : htmlentities($output['output']),
                'exitcode' => $output['exitcode'],
                'runtime' => (float)$output['runtime'],
                'title' => !str_contains($output['flags'], RunRepository::FAILED) ? 'Cronjob successfully ran' : 'Cronjob failed. Please check output below',
                'success' => !str_contains($output['flags'], RunRepository::FAILED)
            ];
        }
        return ['success' => true, 'status' => 'deferred', 'title' => 'Cronjob has been scheduled', 'message' => 'Job was scheduled to be run. You will find the output soon in the job details'];
    }

    private function prepareDockerCommand(string $command, string $service, string|NULL $user): string
    {
        $prepend = 'docker exec ';
        $prepend .= (!empty($user)) ? ' --user=' . $user . ' ' : '';
        $prepend .= $service . ' ';
        return $prepend . $command;
    }

    public function runJob(int $job, bool $manual): array
    {
        global $kernel;
        $starttime = microtime(true);
        $job = $this->getJob($job, true);
        if ($job['data']['crontype'] == 'http') {
            $result = $this->runHttpJob($job);
        } elseif ($job['data']['crontype'] == 'command') {
            $result = $this->runCommandJob($job);
        } elseif ($job['data']['crontype'] == 'reboot') {
            $result = $this->runRebootJob($job, $starttime, $manual);
            if(isset($result['status']) && $result['status'] == 'deferred') return $result;
        }
        $endtime = microtime(true);
        $runtime = $endtime - $starttime;

        // setting flags
        $flags = [];
        if ($result['failed'] === true) {
            $flags[] = RunRepository::FAILED;
        } else {
            $flags[] = RunRepository::SUCCESS;
        }

        if ($manual === true) {
            $flags[] = RunRepository::MANUAL;
        }

        // Remove secrets from output
        if(!empty($job['data']['vars'])) {
            foreach($job['data']['vars'] as $key => $var) {
                if ($var['issecret']) {
                    $result['output'] = str_replace($var['value'], '{'.$key.'}', $result['output']);
                }
            }
        }
        // saving to database
        $this->getEntityManager()->getConnection()->close();
        $runRepo = $this->getEntityManager()->getRepository(Run::class);
        $runRepo->addRun($job['id'], $result['exitcode'], floor($starttime), $runtime, $result['output'], $flags);
        if (!$manual){
            // setting nextrun to next run
            $nextrun = $job['nextrun'];
            do {
                $nextrun = $nextrun + $job['interval'];
            } while ($nextrun < time());


            $addRunSql = 'UPDATE job SET nextrun = :nextrun WHERE id = :id';
            $addRunStmt = $this->getEntityManager()->getConnection()->prepare($addRunSql);
            $addRunStmt->executeQuery([':id' => $job['id'], ':nextrun' => $nextrun]);
        }
        return  ['job_id' =>  $job['id'], 'exitcode' => $result['exitcode'], 'timestamp' =>floor($starttime), 'runtime' => $runtime, 'output' => (string)$result['output'], 'flags' => implode("", $flags)];
    }

    public function unlockJob(int $id = 0): void
    {
        $jobsSql = "UPDATE job SET running = :status WHERE running = 1";
        $params = [':status' => 0];

        if($id != 0) {
            $jobsSql .= " AND id = :id";
            $params[':id'] = $id;
        }
        $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
        $jobsStmt->executeQuery($params);
        return;
    }

    public function isLockedJob(int $id = 0): bool
    {
        $jobsSql = "SELECT id FROM job WHERE id = :id AND running != :status";
        $params = [':status' => 0, ':id' => $id];

        return count($this->getEntityManager()->getConnection()->prepare($jobsSql)->executeQuery($params)->fetchAllAssociative()) > 0;
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
        $addJobSql = "INSERT INTO job(name, data, `interval`, nextrun, lastrun, running) VALUES (:name, :data, :interval, :nextrun, :lastrun, :running)";

        $addJobStmt = $this->getEntityManager()->getConnection()->prepare($addJobSql);
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
        $editJobSql = "UPDATE job SET name = :name, data = :data, `interval` = :interval, nextrun = :nextrun, lastrun = :lastrun WHERE id = :id";

        $editJobStmt = $this->getEntityManager()->getConnection()->prepare($editJobSql);
        $editJobStmt->executeQuery([':name' => $data['name'], ':data' => $data['data'], ':interval' => $data['interval'], ':nextrun' => $data['nextrun'], ':lastrun' => $data['lastrun'],':id' => $id ]);

        return ['success' => true, 'message' => 'Cronjob succesfully edited'];
    }

    public function prepareJob(array $values): array
    {
        if(empty($values['lastrun']) || (isset($values['lastrun-eternal']) && $values['lastrun-eternal'] == 'true')) {
            $values['lastrun'] = NULL;
        } else {
            $values['lastrun'] = DateTime::createFromFormat('d/m/Y H:i:s',$values['lastrun'])->getTimestamp();
        }

        $values['nextrun'] = DateTime::createFromFormat('d/m/Y H:i:s', $values['nextrun'])->getTimestamp();
        $values['data']['retention'] = !empty($values['retention']) ? (int)$values['retention'] : NULL;

        $values['data']['crontype'] = $values['crontype'];
        $values['data']['hosttype'] = $values['hosttype'];
        $values['data']['containertype'] = $values['containertype'];
        $values['data']['fail-pct'] = !empty($values['fail-pct']) ? (int)$values['fail-pct'] : 50;
        $values['data']['fail-days'] = !empty($values['fail-days']) ? (int)$values['fail-days'] : 7;

        if(empty($values['data']['crontype'])) {
            throw new \InvalidArgumentException("Crontype cannot be empty");
        }
        switch($values['data']['crontype'])
        {
            case 'command':
                $values['data']['command'] = $values['command'];
                $values['data']['response'] = explode(',', $values['response']);
                break;
            case 'reboot':
                $values['data']['reboot-command'] = $values['reboot-command'];
                $values['data']['getservices-command'] = $values['getservices-command'];
                $values['data']['getservices-response'] = explode(',',$values['getservices-response']);
                $values['data']['reboot-duration'] = $values['reboot-duration'];
                if(!empty($values['reboot-delay']) || $values['reboot-delay'] == 0) {
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
                $values['data']['http-status'] = explode(',', $values['http-status']);
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
                if($values['data']['crontype'] == 'http') break;
                $values['data']['hosttype'] =  'local';
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
                if(isset($values['privkey-keep']) && $values['privkey-keep'] == true) {
                    $privkeyid = ($privkeyid === NULL) ? count($values['var-value']) : $privkeyid ;
                    $values['var-id'][$privkeyid] = 'ssh-privkey';
                    $values['var-issecret'][$privkeyid] = true;
                    $values['var-value'][$privkeyid] = $values['privkey-orig'];

                }
                break;
        }


        switch($values['data']['containertype']) {
            default:
                if($values['data']['crontype'] == 'http' || $values['data']['crontype'] == 'reboot' ) break;
                $values['data']['containertype'] = 'none';
            case 'none':
                // No options for no container
                break;
            case 'docker':
                $values['data']['service'] = $values['service'];
                $values['data']['container-user'] = $values['container-user'];
                break;
        }

        if(!empty($values['var-value'])) {
            foreach($values['var-value'] as $key => $value) {
                if(!empty($value) || $value == 0) {
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
        $jobStmt = $this->getEntityManager()->getConnection()->prepare($jobSql);
        $jobRslt = $jobStmt->executeQuery([':id' => $id])->fetchAssociative();

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
                $jobRslt['data']['reboot-delay-secs'] = $jobRslt['data']['vars']['reboot-delay-secs']['value'];
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
        $this->getEntityManager()->getConnection()->prepare("DELETE FROM job WHERE id = :id")->executeStatement([':id' => $id]);
        $this->getEntityManager()->getConnection()->prepare("DELETE FROM run WHERE job_id = :id")->executeStatement([':id' => $id]);

        return ['success' => true, 'message' => 'Cronjob succesfully deleted'];
    }
}