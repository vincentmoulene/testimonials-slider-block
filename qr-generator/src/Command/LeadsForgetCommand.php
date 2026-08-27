<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\LeadRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:leads:forget',
    description: 'Deletes one email address from the database (GDPR erasure request).',
)]
final class LeadsForgetCommand extends Command
{
    public function __construct(private readonly LeadRepository $leads)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'The address to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        if ($this->leads->deleteByEmail($email)) {
            $io->success(\sprintf('"%s" has been deleted.', $email));

            return Command::SUCCESS;
        }

        $io->warning(\sprintf('"%s" is not in the database — nothing to delete.', $email));

        return Command::SUCCESS;
    }
}
