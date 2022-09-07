<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\Run;
use App\Entity\User;
use App\Repository\RunRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DemoInstallCommand extends Command
{
    protected static $defaultName = 'webcron:demodata';
    protected $kernel;
    protected $doctrine;
    protected $passwordHasher;

    public function __construct(KernelInterface $kernel, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher)
    {
        $this->kernel = $kernel;
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Install demo data')
            ->setHelp('This command installs the demo data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->doctrine->getManager();

        /** @var Connection $con */
        $con = $this->doctrine->getConnection();
        $con->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');
        $con->executeStatement('TRUNCATE run;');
        $con->executeStatement('TRUNCATE job;');
        $con->executeStatement('TRUNCATE user;');
        $con->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
        $output->writeln('Cleared database');

        $user = new User();
        $hashedpassword = $this->passwordHasher->hashPassword($user, $_ENV['DEMO_PASS']);
        $user
            ->setEmail($_ENV['DEMO_USER'])
            ->setPassword($hashedpassword)
            ->setLocale('en');

        $em->persist($user);
        $em->flush();

        $output->writeln('Created user');

        $jobRepo = $this->doctrine->getRepository(Job::class);
        $job1 = $jobRepo->prepareJob([
            'name' => '[Server] Check if webserver is online',
            'interval' => 300,
            'nextrun' => date('d/m/Y H:i:s', 300 * ceil( time() / 300)),
            'lastrun-eternal' => true,
            'retention' => 30,
            'crontype' => 'command',
            'hosttype' => 'local',
            'containertype' => 'none',
            'fail-pct' => 10,
            'fail-days' => 1,
            'command' => 'ping -c1 {{ server }}',
            'response' => 0,
            'var-id' => [
                'server'
            ],
            'var-value' => [
                'example.com'
            ],
            'var-issecret' => [
                false,
            ]
        ]);

        $job2 = $jobRepo->prepareJob([
            'name' => '[Website] Update texts to latest version',
            'interval' => 7200,
            'nextrun' => date('d/m/Y H:i:s', 7200 * ceil( time() / 7200)),
            'lastrun' => date('d/m/Y H:i:s', (7200 * ceil( time() / 7200)) + 60*60*24*30 ),
            'retention' => 90,
            'crontype' => 'http',
            'url' => 'http://example.com/update.php?password={password}',
            'http-status' => 200,
            'basicauth-username' => 'root',
            'basicauth-password' => 'abc123',
            'hosttype' => '',
            'containertype' => '',

            'var-id' => [
                'password',
            ],
            'var-value' => [
                'letmein',
            ],
            'var-issecret' => [
                true,
            ]
        ]);
        $job3 = $jobRepo->prepareJob([
            'name' => '[Server][Reboot] Monthly reboot',
            'interval' => (60*60*24*30),
            'nextrun' => date('d/m/Y 04:00:00', (60*60*24*30) * ceil( time() / (60*60*24*30))),
            'lastrun-eternal' => true,
            'retention' => 360,
            'crontype' => 'reboot',
            'reboot-command' => 'sudo systemctl reboot',
            'getservices-command' => 'sudo bash /usr/local/bin/checkservices.sh',
            'getservices-response' => 0,
            'reboot-delay' => 30,
            'reboot-duration' => 300,
            'hosttype' => 'ssh',
            'host' => 'server.example.com',
            'user' => 'user',
            'privkey' => '',
            'privkey-password' => 'letmein',
            'containertype' => 'none',
            'var-id' => [
            ],
            'var-value' => [
            ],
            'var-issecret' => [
            ]
        ]);
        $em->persist($job1);
        $em->persist($job2);
        $em->persist($job3);
        $em->flush();

        $output->writeln('Created jobs');

        $run = new Run();
        $run->setExitcode(0)
            ->setJob($job1)
            ->setRuntime(rand(0, 5000) / 1000)
            ->setOutput('PING example.com (93.184.216.34) 56(84) bytes of data.
64 bytes from 93.184.216.34 (93.184.216.34): icmp_seq=1 ttl=54 time=99.8 ms

--- example.com ping statistics ---
1 packets transmitted, 1 received, 0% packet loss, time 0ms
rtt min/avg/max/mdev = 99.771/99.771/99.771/0.000 ms')
            ->setTimestamp(300 * ceil( time() / 300) - (5 * 300))
            ->setFlags(RunRepository::SUCCESS);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(0)
            ->setJob($job1)
            ->setRuntime(rand(0, 5000) / 1000)
            ->setOutput('PING example.com (93.184.216.34) 56(84) bytes of data.
64 bytes from 93.184.216.34 (93.184.216.34): icmp_seq=1 ttl=54 time=97.8 ms

--- example.com ping statistics ---
1 packets transmitted, 1 received, 0% packet loss, time 0ms
rtt min/avg/max/mdev = 97.804/97.804/97.804/0.000 ms')
            ->setTimestamp(300 * ceil( time() / 300) - (4 * 300))
            ->setFlags(RunRepository::SUCCESS);
        $em->persist($run);
        $run = new Run();
        $run->setExitcode(0)
            ->setJob($job1)
            ->setRuntime(rand(0, 5000) / 1000)
            ->setOutput('PING example.com (93.184.216.34) 56(84) bytes of data.
64 bytes from 93.184.216.34 (93.184.216.34): icmp_seq=1 ttl=54 time=101 ms

--- example.com ping statistics ---
1 packets transmitted, 1 received, 0% packet loss, time 0ms
rtt min/avg/max/mdev = 101.362/101.362/101.362/0.000 ms')
            ->setTimestamp(300 * ceil( time() / 300) - (3 * 300))
            ->setFlags(RunRepository::SUCCESS);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(2)
            ->setJob($job1)
            ->setRuntime(rand(5000, 10000) / 1000)
            ->setOutput('ping: example.com: Name or service not known')
            ->setTimestamp(300 * ceil( time() / 300) - (2 * 300))
            ->setFlags(RunRepository::FAILED);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(2)
            ->setJob($job1)
            ->setRuntime(rand(5000, 10000) / 1000)
            ->setOutput('ping: example.com: Name or service not known')
            ->setTimestamp(300 * ceil( time() / 300) - (1 * 300))
            ->setFlags(RunRepository::FAILED);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(2)
            ->setJob($job1)
            ->setRuntime(rand(5000, 10000) / 1000)
            ->setOutput('ping: example.com: Name or service not known')
            ->setTimestamp(300 * ceil( time() / 300) - (52))
            ->setFlags(RunRepository::FAILED . RunRepository::MANUAL);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(200)
            ->setJob($job2)
            ->setRuntime(rand(0, 10000) / 1000)
            ->setOutput(json_encode(['success' => true, 'message' => 'Texts are updated succesfully']))
            ->setTimestamp(7200 * ceil( time() / 7200) - (4 * 7200))
            ->setFlags(RunRepository::SUCCESS);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(200)
            ->setJob($job2)
            ->setRuntime(rand(0, 10000) / 1000)
            ->setOutput(json_encode(['success' => true, 'message' => 'Texts are updated succesfully']))
            ->setTimestamp(7200 * ceil( time() / 7200) - (7200))
            ->setFlags(RunRepository::SUCCESS);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(200)
            ->setJob($job2)
            ->setRuntime(rand(0, 10000) / 1000)
            ->setOutput(json_encode(['success' => true, 'message' => 'Texts are updated succesfully']))
            ->setTimestamp(7200 * ceil( time() / 7200) - (2 * 7200))
            ->setFlags(RunRepository::SUCCESS);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(500)
            ->setJob($job2)
            ->setRuntime(rand(0, 10000) / 1000)
            ->setOutput(json_encode(['success' => false, 'message' => 'Zipfile was not readable']))
            ->setTimestamp(7200 * ceil( time() / 7200) - (3 * 7200))
            ->setFlags(RunRepository::FAILED);
        $em->persist($run);

        $run = new Run();
        $run->setExitcode(500)
            ->setJob($job2)
            ->setRuntime(rand(0, 10000) / 1000)
            ->setOutput(json_encode(['success' => false, 'message' => 'Zipfile was not readable']))
            ->setTimestamp(7200 * ceil( time() / 7200) - (5 * 7200))
            ->setFlags(RunRepository::FAILED);
        $em->persist($run);

        $em->flush();

        $output->writeln('Created runs');

        return Command::SUCCESS;
    }
}