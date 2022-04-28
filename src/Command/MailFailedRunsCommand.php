<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailFailedRunsCommand extends Command
{
    protected static $defaultName = 'mail-failed-runs';
    protected $kernel;
    protected $doctrine;
    protected $templating;
    protected $mailer;

    public function __construct(KernelInterface $kernel, ManagerRegistry $doctrine, Environment $templating, MailerInterface $mailer)
    {
        $this->kernel = $kernel;
        $this->doctrine = $doctrine;
        $this->templating = $templating;
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends email about failed runs')
            ->setHelp('This command will send emails to the users when jobs are failing');
    }

    /**
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\LoaderError
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $userRepo = $this->doctrine->getRepository(User::class);
        $jobRepo = $this->doctrine->getRepository(Job::class);

        $failedJobs = $jobRepo->getFailingJobs();

        if(!empty($failedJobs)) {
            $html = $this->templating->render('mail-failed-runs.html.twig', ['jobs' => $failedJobs]);

            $email = (new Email())
                ->from($_ENV['MAILER_FROM'])
                ->subject('Some cronjobs are failing')
                ->html($html);

            $recipients = $userRepo->getMailAddresses();
            foreach ($recipients as $recipient) {
                $email->addTo($recipient);
            }

            $this->mailer->send($email);

            $output->writeln('Message sent');
        }

        return Command::SUCCESS;
    }
}