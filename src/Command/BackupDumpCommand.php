<?php

namespace App\Command;

use App\Utils\Maintenance;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'backup:dump',
    description: 'Dumps the local database to a file',
)]
class BackupDumpCommand extends Command
{
    public function __construct(private Maintenance $maintenance)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->maintenance->dumpDb();

        $io->success('A backup of the database has been created');

        return Command::SUCCESS;
    }
}
