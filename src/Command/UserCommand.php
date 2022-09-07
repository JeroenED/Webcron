<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\Run;
use App\Entity\User;
use App\Repository\RunRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCommand extends Command
{
    protected static $defaultName = 'webcron:user';
    protected KernelInterface $kernel;
    protected ManagerRegistry $doctrine;
    protected UserPasswordHasherInterface  $passwordHasher;
    protected SymfonyStyle $io;

    private $action;
    private $username;
    private $password;
    private $confirm;

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
            ->setDescription('User stuff')
            ->setHelp('The command is doing user stuff')
            ->addArgument('action', InputArgument::REQUIRED, 'What action should be executed? [add, delete, update]', null, ['add', 'update', 'delete'])
            ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'What action should be executed? [add, delete, update]', '')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'What action should be executed? [add, delete, update]', '');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->action = $input->getArgument('action');
        $this->username = $input->getOption('username');
        $this->password = $input->getOption('password');
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if(!empty($this->password)) {
            $this->io->warning('It is not safe to send password directly via STDIN');
        }

        if(in_array($this->action, ['add'])) {
            if (empty($this->username)) {
                $this->username = $this->io->ask('Please provide the username? ');
            }
        }

        if(in_array($this->action, ['update', 'delete'])) {
            if (empty($this->username)) {
                $users = $this->doctrine->getRepository(User::class)->findAll();
                $choices = [];
                foreach($users as $user) {
                    $choices[] = $user->getEmail();
                }
                if(count($choices) > 1) {
                    $this->username = $this->io->choice('Please provide the username? ', $choices);
                } else {
                    $this->username = $choices[0];
                    $this->io->info('Selected user ' . $this->username);
                }
            }
        }

        if(in_array($this->action, ['add', 'update'])) {
            if(empty($this->password)) {
                $password1 = $this->io->askHidden('Please enter the password? ');
                $password2 = $this->io->askHidden('Please confirm the password? ');

                if ($password1 != $password2) {
                    $this->io->error('Passwords didn\'t match. Exiting');
                } elseif ($password1 == '') {
                    $this->io->error('Passwords cannot be empty. Exiting');
                } else {
                    $this->password = $password1;
                }
            }
        } elseif ($this->action == 'delete') {
            $this->confirm = $this->io->confirm('Are you sure you want to delete ' . $this->username . '? ', false);
        }

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($this->action) {
            case 'add':
                $return = $this->createUser();
                break;
            case 'delete':
                $return = $this->deleteUser();
                break;
            case 'update':
                $return = $this->updateUser();
                break;
        }
        return $return;
    }

    private function createUser() {

        $em = $this->doctrine->getManager();

        $user = $em->getRepository(User::class)->findOneBy(['email' => $this->username]);

        if ($user !== NULL) {
            $this->io->error('User already exists');
            return Command::FAILURE;
        }

        if ($this->password === NULL) {
            $this->io->error('Passwords didn\'t match. Exiting');
            return Command::FAILURE;
        }

        /** @var Connection $con */
        $user = new User();
        $hashedpassword = $this->passwordHasher->hashPassword($user, $this->password);
        $userSendMail = $em->getRepository(User::class)->findOneBy(['sendmail' => 1]);
        $user
            ->setEmail($this->username)
            ->setPassword($hashedpassword)
            ->setSendmail($userSendMail === NULL);

        $em->persist($user);
        $em->flush();

        $this->io->success('User created');

        return Command::SUCCESS;
    }

    private function updateUser() {

        $em = $this->doctrine->getManager();

        $user = $em->getRepository(User::class)->findOneBy(['email' => $this->username]);

        if ($user === NULL) {
            $this->io->error('User does not exist');
            return Command::FAILURE;
        }

        if ($this->password === NULL) {
            return Command::FAILURE;
        }

        $hashedpassword = $this->passwordHasher->hashPassword($user, $this->password);
        $user
            ->setEmail($this->username)
            ->setPassword($hashedpassword);

        $em->persist($user);
        $em->flush();

        $this->io->success('User updated');

        return Command::SUCCESS;
    }

    private function deleteUser() {

        if(!$this->confirm) {
            $this->io->info('User not deleted');
            return Command::SUCCESS;
        }
        $em = $this->doctrine->getManager();

        $user = $em->getRepository(User::class)->findOneBy(['email' => $this->username]);

        if ($user === NULL) {
            $this->io->error('User does not exist');
            return Command::FAILURE;
        }

        $em->remove($user);
        $em->flush();
        $em->clear();

        $this->io->success('User deleted');

        return Command::SUCCESS;
    }
}