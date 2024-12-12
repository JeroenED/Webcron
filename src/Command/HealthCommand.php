<?php

namespace App\Command;

use App\Service\DaemonHelpers;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'webcron:health', description: 'Gets the health of the app')]
class HealthCommand extends Command
{
    private DaemonHelpers $daemonHelpers;
    public function __construct(DaemonHelpers $daemonHelpers)
    {
        $this->daemonHelpers = $daemonHelpers;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('human-readable', 'H', InputOption::VALUE_NEGATABLE, 'Provide output in human readable style')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $return = $this->daemonHelpers->healthCheck();
        if ($input->getOption('human-readable')) {
            $io = new SymfonyStyle($input, $output);
            $io->table(['name', 'value'], $return);
        } else {
            $output->writeln(json_encode($return));
        }
        return $return['DaemonRunning'] ? Command::SUCCESS : Command::FAILURE;
    }
}