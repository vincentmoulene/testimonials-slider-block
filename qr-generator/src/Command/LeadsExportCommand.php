<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Lead;
use App\Repository\LeadRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:leads:export',
    description: 'Exports the collected email addresses as CSV (stdout by default).',
)]
final class LeadsExportCommand extends Command
{
    public function __construct(private readonly LeadRepository $leads)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write to this file instead of stdout')
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only leads created on or after this date (e.g. 2026-01-01, "-7 days")')
            ->addOption('consented-only', null, InputOption::VALUE_NONE, 'Only addresses that opted in to marketing — the ones you may email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $since = null;
        if (\is_string($sinceOption = $input->getOption('since'))) {
            try {
                $since = new \DateTimeImmutable($sinceOption);
            } catch (\Exception) {
                (new SymfonyStyle($input, $output))->error(\sprintf('Cannot understand the date "%s".', $sinceOption));

                return Command::INVALID;
            }
        }

        $destination = \is_string($input->getOption('output')) ? $input->getOption('output') : null;
        $handle = null !== $destination ? fopen($destination, 'wb') : fopen('php://stdout', 'wb');
        if (false === $handle) {
            return Command::FAILURE;
        }

        fputcsv($handle, ['email', 'locale', 'tool', 'consent_marketing', 'generation_count', 'created_at', 'last_seen_at', 'source'], escape: '');

        $exported = 0;
        /** @var Lead $lead */
        foreach ($this->leads->streamAll($since, (bool) $input->getOption('consented-only')) as $lead) {
            fputcsv($handle, [
                $lead->getEmail(),
                $lead->getLocale(),
                $lead->getTool(),
                $lead->hasConsentedToMarketing() ? '1' : '0',
                $lead->getGenerationCount(),
                $lead->getCreatedAt()->format(\DATE_ATOM),
                $lead->getLastSeenAt()->format(\DATE_ATOM),
                $lead->getSource() ?? '',
            ], escape: '');
            ++$exported;
        }

        fclose($handle);

        // Never mix the report with the CSV itself: the export is often piped.
        $report = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $stats = $this->leads->stats();
        $report->writeln(\sprintf(
            '<info>%d exported</info> — %d addresses in total, %d of them opted in to marketing.',
            $exported,
            $stats['total'],
            $stats['consented'],
        ));

        return Command::SUCCESS;
    }
}
