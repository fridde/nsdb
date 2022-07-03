<?php

namespace App\Command;

use App\Utils\Maintenance;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deploy',
    description: 'Deploys the app to the ftp-server',
)]
class DeployCommand extends Command
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

        $output->writeln(getenv('APP_ENV'));

        //$this->maintenance->deployToFtp();


        $io->success('The files have been deployed to the ftp-server.');

        return Command::SUCCESS;
    }
}
