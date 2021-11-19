<?php

namespace JeroenED\Webcron\Command;

use JeroenED\Framework\Kernel;
use JeroenED\Framework\Twig;
use JeroenED\Webcron\Repository\Job;
use JeroenED\Webcron\Repository\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailFailedRunsCommand extends Command
{
    protected static $defaultName = 'mail-failed-runs';
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends email about failed runs')
            ->setHelp('This command will send emails to the users when jobs are failing');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $userRepo = new User($this->kernel->getDbCon());
        $jobRepo = new Job($this->kernel->getDbCon());

        $failedJobs = $jobRepo->getFailingJobs();

        if(!empty($failedJobs)) {
            $twig = new Twig($this->kernel);
            $html = $twig->render('mail-failed-runs.html.twig', ['jobs' => $failedJobs]);
            $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from($_ENV['MAILER_FROM'])
                ->subject('Some cronjobs are failing')
                ->html($html);


            $recipients = $userRepo->getMailAddresses();
            foreach ($recipients as $recipient) {
                $email->addTo($recipient);
            }

            $mailer->send($email);

            $output->writeln('Message sent');
        }


        return Command::SUCCESS;
    }
}