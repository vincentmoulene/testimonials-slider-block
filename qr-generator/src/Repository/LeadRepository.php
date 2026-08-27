<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lead>
 */
final class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    /**
     * Creates the lead on first sight, and counts the generation afterwards.
     * Two concurrent first-time submissions of the same address end up as one
     * row: the loser of the race re-reads and updates.
     */
    public function record(
        string $email,
        string $locale,
        string $tool,
        ?string $source = null,
        ?string $ipHash = null,
        bool $consentMarketing = false,
    ): Lead {
        $email = mb_strtolower(trim($email));
        $manager = $this->getEntityManager();

        $lead = $this->findOneBy(['email' => $email]);
        if (null === $lead) {
            $lead = (new Lead($email, $locale, $tool))
                ->setSource($source)
                ->setIpHash($ipHash)
                ->setConsentMarketing($consentMarketing);

            try {
                $manager->persist($lead);
                $manager->flush();

                return $lead;
            } catch (UniqueConstraintViolationException) {
                $manager->clear();
                $lead = $this->findOneBy(['email' => $email]);
                if (null === $lead) {
                    throw new \RuntimeException('Unable to record the lead.');
                }
            }
        }

        $lead->recordGeneration($locale, $tool)->setConsentMarketing($consentMarketing);
        if (null === $lead->getSource()) {
            $lead->setSource($source);
        }
        $manager->flush();

        return $lead;
    }

    public function deleteByEmail(string $email): bool
    {
        $lead = $this->findOneBy(['email' => mb_strtolower(trim($email))]);
        if (null === $lead) {
            return false;
        }

        $this->getEntityManager()->remove($lead);
        $this->getEntityManager()->flush();

        return true;
    }

    /** @return iterable<Lead> */
    public function streamAll(?\DateTimeImmutable $since = null, bool $onlyConsented = false): iterable
    {
        $qb = $this->createQueryBuilder('l')->orderBy('l.createdAt', 'ASC');

        if (null !== $since) {
            $qb->andWhere('l.createdAt >= :since')->setParameter('since', $since);
        }
        if ($onlyConsented) {
            $qb->andWhere('l.consentMarketing = true');
        }

        return $qb->getQuery()->toIterable();
    }

    /** @return array{total: int, consented: int} */
    public function stats(): array
    {
        /** @var array{total: int|string, consented: int|string|null} $row */
        $row = $this->createQueryBuilder('l')
            ->select('COUNT(l.id) AS total', 'SUM(CASE WHEN l.consentMarketing = true THEN 1 ELSE 0 END) AS consented')
            ->getQuery()
            ->getSingleResult();

        return ['total' => (int) $row['total'], 'consented' => (int) ($row['consented'] ?? 0)];
    }
}
