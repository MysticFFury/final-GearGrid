<?php

namespace App\Repository;

use App\Entity\StockMovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockMovement>
 */
class StockMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMovement::class);
    }

    /**
     * @return list<StockMovement>
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.product', 'p')->addSelect('p')
            ->leftJoin('s.createdBy', 'u')->addSelect('u')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<StockMovement>
     */
    public function findFiltered(
        ?string $userSearch,
        ?int $productId,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to,
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.product', 'p')->addSelect('p')
            ->leftJoin('s.createdBy', 'u')->addSelect('u')
            ->orderBy('s.createdAt', 'DESC');

        if ($userSearch !== null && $userSearch !== '') {
            $qb->andWhere('LOWER(u.name) LIKE :u OR LOWER(u.email) LIKE :u')
                ->setParameter('u', '%'.strtolower($userSearch).'%');
        }

        if ($productId !== null && $productId > 0) {
            $qb->andWhere('p.id = :pid')
                ->setParameter('pid', $productId);
        }

        if ($from !== null) {
            $qb->andWhere('s.createdAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('s.createdAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }
}
