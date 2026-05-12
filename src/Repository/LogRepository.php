<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Fetch logs with optional filters.
     *
     * @return Log[]
     */
    public function findFiltered(
        ?string $userName,
        ?string $action,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($userName) {
            $qb->andWhere('LOWER(l.userName) LIKE :userName')
               ->setParameter('userName', '%'.strtolower($userName).'%');
        }

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($from) {
            $qb->andWhere('l.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('l.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return string[]
     */
    public function findDistinctActions(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('DISTINCT l.action AS action')
            ->orderBy('l.action', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $actions = array_column($rows, 'action');
        
        // Define preferred order for actions
        $preferredOrder = ['LOGIN', 'LOGOUT', 'ADD', 'UPDATE', 'DELETE', 'STOCK_ADD', 'CREATE'];
        $orderedActions = [];
        $otherActions = [];
        
        // Sort actions by preferred order
        foreach ($preferredOrder as $preferredAction) {
            if (in_array($preferredAction, $actions)) {
                $orderedActions[] = $preferredAction;
            }
        }
        
        // Add any other actions that aren't in the preferred list
        foreach ($actions as $action) {
            if (!in_array($action, $preferredOrder)) {
                $otherActions[] = $action;
            }
        }
        
        return array_merge($orderedActions, $otherActions);
    }

    /**
     * @return string[]
     */
    public function findDistinctUsers(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('DISTINCT l.userName AS userName')
            ->where('l.userName IS NOT NULL')
            ->orderBy('l.userName', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'userName');
    }
}
