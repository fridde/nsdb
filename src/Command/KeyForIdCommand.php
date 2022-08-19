<?php

namespace App\Command;

use App\Security\Key\ApiKeyManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:key-for-id',
    description: 'Creates a valid AuthKey (cookie) or url key for a certain user',
)]
class KeyForIdCommand extends Command
{
    public function __construct(private ApiKeyManager $akm)
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'Can be anonymous(a), cookie (c), cron (t) or url (u)')
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'The id to encode')
            ->addOption('ver', null, InputOption::VALUE_OPTIONAL, 'the version for this key')
            ->addOption('secret', 's', InputOption::VALUE_OPTIONAL, 'APP_SECRET as given by the environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getArgument('type');
        $type = strtolower(substr($type, 0, 1));
        $id = $input->getOption('id') ?? '';
        $version = $input->getOption('version');
        $secret = $input->getOption('secret');

        $version = empty($version) ? null : $version;
        $secret = empty($secret) ? null : $secret; // to avoid empty strings

        $key = $this->akm->createKeyFromValues($type, $id, $version);
        if(!empty($secret)){
            $this->akm->changeAppSecretTemporarily($secret);
        }
        $output->writeln($this->akm->createCodeStringForKey($key));

        //$io->success('');

        return Command::SUCCESS;
    }
}
