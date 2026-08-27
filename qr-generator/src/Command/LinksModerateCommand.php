<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\PublicLink;
use App\Repository\PublicLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:links:moderate',
    description: 'Hides a link, or every link of a host, from the public gallery.',
)]
final class LinksModerateCommand extends Command
{
    public function __construct(
        private readonly PublicLinkRepository $links,
        private readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('target', InputArgument::REQUIRED, 'A full URL, or a host to hide entirely (example.com)')
            ->addOption('unblock', null, InputOption::VALUE_NONE, 'Put it back in the gallery instead')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Remove the rows outright rather than hiding them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $target = trim((string) $input->getArgument('target'));

        $matches = str_contains($target, '://')
            ? array_filter([$this->links->findOneBy(['urlHash' => hash('sha256', $target)])])
            : $this->links->findByHost($target);

        if ([] === $matches) {
            $io->warning(\sprintf('Nothing matches "%s".', $target));

            return Command::SUCCESS;
        }

        $delete = (bool) $input->getOption('delete');
        $unblock = (bool) $input->getOption('unblock');

        /** @var PublicLink $link */
        foreach ($matches as $link) {
            $delete ? $this->manager->remove($link) : $link->block(!$unblock);
        }
        $this->manager->flush();

        $io->success(\sprintf(
            '%d link(s) %s.',
            \count($matches),
            $delete ? 'deleted' : ($unblock ? 'put back in the gallery' : 'hidden from the gallery'),
        ));

        return Command::SUCCESS;
    }
}
