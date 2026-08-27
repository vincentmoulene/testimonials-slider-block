<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PublicLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PublicLink>
 */
final class PublicLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicLink::class);
    }

    /**
     * Records a link the first time it is turned into a code, and counts the
     * repeats afterwards. A blocked link is never resurrected by a new hit.
     */
    public function record(string $url, string $host, string $locale): PublicLink
    {
        $manager = $this->getEntityManager();
        $existing = $this->findOneBy(['urlHash' => hash('sha256', $url)]);

        if (null === $existing) {
            $link = new PublicLink($url, $host, $locale);
            try {
                $manager->persist($link);
                $manager->flush();

                return $link;
            } catch (UniqueConstraintViolationException) {
                $manager->clear();
                $existing = $this->findOneBy(['urlHash' => hash('sha256', $url)]);
                if (null === $existing) {
                    throw new \RuntimeException('Unable to record the link.');
                }
            }
        }

        $existing->recordHit();
        $manager->flush();

        return $existing;
    }

    /** @return array<int, PublicLink> newest first */
    public function latest(int $limit = 25): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.blocked = false')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: array<int, PublicLink>, total: int, pages: int, page: int}
     */
    public function paginate(int $page, int $perPage = 60, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.blocked = false')
            ->orderBy('l.createdAt', 'DESC');

        if ('' !== $search) {
            $qb->andWhere('LOWER(l.url) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $paginator = new Paginator($qb->getQuery(), false);
        $total = \count($paginator);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.blocked = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<int, PublicLink> */
    public function findByHost(string $host): array
    {
        return $this->findBy(['host' => mb_strtolower($host)]);
    }
}
