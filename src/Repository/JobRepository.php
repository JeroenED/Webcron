<?php


namespace App\Repository;


use App\Entity\Job;
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
        /** @var Job[] $jobs */
        $jobs = $this->getAllJobs();

        $return = [];
        foreach($jobs as $job) {
            if($job->getData('needschecking')) {
                $return[] = $job;
            }
        }
        return $return;
    }

    public function getRunningJobs(bool $idiskey = false): array
    {
        $qb = $this->createQueryBuilder('job');
        return $qb
            ->where('job.running != 0')
            ->getQuery()->getResult();
    }

    public function getAllJobs(bool $idiskey = false)
    {
        $qb = $this->createQueryBuilder('job');

        $jobs = $qb->where('job.id = job.id');

        if($idiskey) {
            $jobs = $jobs->orderBy('job.id');
        } else {
            $jobs = $jobs
                ->orderBy('job.name')
                ->addOrderBy("JSON_VALUE(job.data, '$.host')")
                ->addOrderBy("JSON_VALUE(job.data, '$.service')");
        }

        /** @var Job $jobs */
        $jobs = $jobs->getQuery()->getResult();

        return $this->parseJobs($jobs);
    }

    public function parseJobs(array $jobs): array
    {
        $runRepo = $this->getEntityManager()->getRepository(Run::class);

        foreach ($jobs as $key=>&$job) {
            $jobData = $job->getData();
            $job->setData('host-displayname', $jobData['host']);
            $job->setData('host', $jobData['host']);
            $job->setData('service', $jobData['service'] ?? '');
            $job->setData('norun', $job->getLastrun() !== null && $job->getNextrun() > $job->getLastrun());
            $job->setData('running', $job->getRunning() != 0);
            $failedruns = $runRepo->getRunsForJob($job->getId(), true, $jobData['fail-days']);
            $failed = count($failedruns);
            $all = count($runRepo->getRunsForJob($job->getId(), false, $jobData['fail-days']));
            $job->setData('lastfail', $failedruns[0] ?? NULL);
            $job->setData('needschecking', $all > 0  && (($failed / $all) * 100) > $jobData['fail-pct']);
            if(!empty($jobData['containertype']) && $jobData['containertype'] != 'none') {
                $job->setData('host-displayname', $jobData['service'] . ' on ' . $jobData['host']);
            }
        }

        return $jobs;
    }

    public function getJobsDue()
    {
        $qb = $this->createQueryBuilder('job');
        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->lte('job.nextrun', ':timestamp'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('job.lastrun'),
                        $qb->expr()->gt('job.lastrun', ':timestamp')
                    ),
                    $qb->expr()->in('job.running', [0,2])
                )
            )
            ->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->notIn('job.running', [0,1,2]),
                    $qb->expr()->lt('job.running', ':timestamp')
                )
            )
            ->orWhere('job.running = 2')
            ->orderBy('job.running', 'DESC')
            ->addOrderBy('job.nextrun', 'ASC')
            ->setParameter(':timestamp', time())
            ->getQuery()->getResult();
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

    private function runHttpJob(Job $job): array
    {
        $client = new Client();

        if(!empty($job->getData('vars'))) {
            foreach($job->getData('vars') as $key => $var) {
                if (!empty($job->getData('basicauth-username'))) $job->setData('basicauth-username', str_replace('{' . $key . '}', $var['value'], $job->getData('basicauth-username')));
                $job->setData('url', str_replace('{' . $key . '}', $var['value'], $job->getData('url')));
            }
        }

        $url = $job->getData('url');
        $options['http_errors'] = false;
        $options['auth'] = !empty($job->getData('basicauth-username')) ? [$job->getData('basicauth-username'), $job->getData('basicauth-password')] : NULL;
        try {
            $res = $client->request('GET', $url, $options);
            $return['exitcode'] = $res->getStatusCode();
            $return['output'] = $res->getBody();
            $return['failed'] = !in_array($return['exitcode'], $job->getData('http-status'));
        } catch(GuzzleException $exception) {
            $return['exitcode'] = $exception->getCode();
            $return['output'] = $exception->getMessage();
            $return['failed'] = true;
        }

        return $return;
    }

    private function runCommandJob(Job $job): array
    {
        if(!empty($job->getData('vars'))) {
            foreach ($job->getData('vars') as $key => $var) {
                $job->setData('command', str_replace('{' . $key . '}', $var['value'], $job->getData('command')));
            }
        }

        $command = $job->getData('command');
        if ($job->getData('containertype') == 'docker') {
            $command = $this->prepareDockerCommand($command, $job->getData('service'), $job->getData('container-user'));
        }
        try {
            if($job->getData('hosttype') == 'local') {
                $return = $this->runLocalCommand($command);
            } elseif($job->getData('hosttype') == 'ssh') {
                $return = $this->runSshCommand($command, $job->getData('host'), $job->getData('user'), $job->getData('ssh-privkey'), $job->getData('privkey-password'));
            }
            $return['failed'] = !in_array($return['exitcode'], $job->getData('response'));
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

    private function runSshCommand(string $command, string $host, string $user, ?string $privkey, ?string $password): array
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

    private function runRebootJob(Job $job, float &$starttime, bool &$manual): array
    {
        if($job->getRunning() == 1) {
            $this->setTempVar($job->getId(), 'starttime', $starttime);
            $this->setTempVar($job->getId(), 'manual', $manual);
            $job->setData('reboot-command', str_replace('{reboot-delay}', $job['data']['reboot-delay'], $job->getData('reboot-command')));
            $job->setData('reboot-command', str_replace('{reboot-delay-secs}', $job['data']['reboot-delay-secs'], $job->getData('reboot-command')));

            if (!empty($job->getData('vars'))) {
                foreach ($job->getData('vars') as $key => $var) {
                    $job->setData('reboot-command', str_replace('{' . $key . '}', $var['value'], $job->getData('reboot-command')));
                }
            }

            $jobsSql = "UPDATE job SET running = :status WHERE id = :id";
            $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
            $jobsStmt->executeQuery([':id' => $job->getId(), ':status' => time() + $job['data']['reboot-delay-secs'] + ($job['data']['reboot-duration'] * 60)]);

            try {
                if($job->getData('hosttype') == 'local') {
                    $this->runLocalCommand($job->getData('reboot-command'));
                } elseif($job->getData('hosttype') == 'ssh') {
                    $this->runSshCommand($job->getData('reboot-command'), $job->getData('host'), $job->getData('user'), $job->getData('ssh-privkey') ?? '', $job->getData('privkey-password') ?? '');
                }
            } catch (\RuntimeException $exception) {
                $return['exitcode'] = $exception->getCode();
                $return['output'] = $exception->getMessage();
                $return['failed'] = true;
                return $return;
            }
            return ['status' => 'deferred'];

        } elseif($job->getRunning() != 0) {
            if($job->getRunning() > time()) {
                return ['status' => 'deferred'];
            }
            $starttime = (float)$this->getTempVar($job->getId(), 'starttime');
            $this->deleteTempVar($job->getId(), 'starttime');
            $manual = $this->getTempVar($job->getId(), 'manual');
            $this->deleteTempVar($job->getId(), 'manual');

            $jobsSql = "UPDATE job SET running = :status WHERE id = :id";
            $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
            $jobsStmt->executeQuery([':id' => $job->getId(), ':status' => 1]);

            if (!empty($job->getData('vars'))) {
                foreach ($job->getData('vars') as $key => $var) {
                    $job->setData('getservices-command', str_replace('{' . $key . '}', $var['value'], $job->getData('getservices-command')));
                }
            }
            try {
                if($job->getData('hosttype') == 'local') {
                    $return = $this->runLocalCommand($job->getData('getservices-command'));
                } elseif($job->getData('hosttype') == 'ssh') {
                    $return = $this->runSshCommand($job->getData('getservices-command'), $job->getData('host'), $job->getData('user'), $job->getData('ssh-privkey') ?? '', $job->getData('privkey-password') ?? '');
                }
            } catch (\RuntimeException $exception) {
                $return['exitcode'] = $exception->getCode();
                $return['output'] = $exception->getMessage();
                $return['failed'] = true;
                return $return;
            }
            $return['failed'] = !in_array($return['exitcode'], $job->getData('getservices-response'));
            return $return;
        }
        return ['success' => false, 'message' => 'You probably did something clearly wrong'];
    }

    public function runNow($job, $console = false) {
        $job = $this->getJob($job, true);
        $runRepo = $this->getEntityManager()->getRepository(Run::class);

        if($console == false && ($runRepo->isSlowJob($job->getId()) || count($job->getRuns()) == 0 || $job->getData('crontype') === 'reboot')) {
            $jobsSql = "UPDATE job SET running = :status WHERE id = :id AND running IN (0,1,2)";
            $jobsStmt = $this->getEntityManager()->getConnection()->prepare($jobsSql);
            $jobsStmt->executeQuery([':id' => $job->getId(), ':status' => 2]);
        } else {
            $output = $this->runJob($job->getId(), true);
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
        $starttime = microtime(true);
        $job = $this->getJob($job, true);
        if ($job->getData('crontype') == 'http') {
            $result = $this->runHttpJob($job);
        } elseif ($job->getData('crontype') == 'command') {
            $result = $this->runCommandJob($job);
        } elseif ($job->getData('crontype') == 'reboot') {
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
        if(!empty($job->getData('vars'))) {
            foreach($job->getData('vars') as $key => $var) {
                if ($var['issecret']) {
                    $result['output'] = str_replace($var['value'], '{'.$key.'}', $result['output']);
                }
            }
        }
        // saving to database
        $this->getEntityManager()->getConnection()->close();
        $runRepo = $this->getEntityManager()->getRepository(Run::class);
        $runRepo->addRun($job->getId(), $result['exitcode'], floor($starttime), $runtime, $result['output'], $flags);
        if (!$manual){
            // setting nextrun to next run
            $nextrun = $job->getNextrun();
            do {
                $nextrun = $nextrun + $job->getInterval();
            } while ($nextrun < time());


            $addRunSql = 'UPDATE job SET nextrun = :nextrun WHERE id = :id';
            $addRunStmt = $this->getEntityManager()->getConnection()->prepare($addRunSql);
            $addRunStmt->executeQuery([':id' => $job->getId(), ':nextrun' => $nextrun]);
        }
        return  ['job_id' =>  $job->getId(), 'exitcode' => $result['exitcode'], 'timestamp' =>floor($starttime), 'runtime' => $runtime, 'output' => (string)$result['output'], 'flags' => implode("", $flags)];
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
        $job = $this->find($id);

        if(!empty($job->getData('vars'))) {
            foreach ($job->getData('vars') as $key => &$value) {
                if ($value['issecret']) {
                    $job->setData('vars.' . $key . '.value', ($withSecrets) ? Secret::decrypt(base64_decode($value['value'])) : '');
                }
            }
        }

        switch($job->getData('crontype')) {
            case 'http':
                if($job->hasData('vars.basicauth-password.value')) {
                    $job->setData('basicauth-password', $job->getData('vars.basicauth-password.value'));
                    $job->removeData('vars.basicauth-password');
                }
                break;
            case 'reboot':
                $job->setData('reboot-delay', $job->getData('vars.reboot-delay.value'));
                $job->setData('reboot-delay-secs', $job->getData('vars.reboot-delay-secs.value'));

                $job->removeData('vars.reboot-delay');
                $job->removeData('vars.reboot-delay-secs');
                break;
        }

        switch($job->getData('hosttype')) {
            case 'ssh':
                if($job->hasData('vars.ssh-privkey.value')) {
                    $job->setData('ssh-privkey', $job->getData('vars.ssh-privkey.value'));
                    $job->removeData('vars.ssh-privkey');
                }
                if($job->hasData('vars.privkey-password.value')) {
                    $job->setData('privkey-password', $job->getData('vars.privkey-password.value'));
                    $job->removeData('vars.privkey-password');
                }
                break;
        }
        if($job->getData('crontype') == 'http') {
        }
        return $job;
    }

    public function deleteJob(int $id)
    {
        $em = $this->getEntityManager();

        $job = $this->find($id);
        $em->remove($job);
        $em->flush();

        return ['success' => true, 'message' => 'Cronjob succesfully deleted'];
    }
}