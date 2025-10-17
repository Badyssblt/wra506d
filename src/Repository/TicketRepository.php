<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function getTicketStatistics(): array
    {
        $qb = $this->createQueryBuilder('t');

        // Total tickets
        $total = $qb->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // By status
        $byStatus = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        // By priority
        $byPriority = $this->createQueryBuilder('t')
            ->select('t.priority as priority, COUNT(t.id) as count')
            ->groupBy('t.priority')
            ->getQuery()
            ->getResult();

        // By category
        $byCategory = $this->createQueryBuilder('t')
            ->select('c.name as category, COUNT(t.id) as count')
            ->leftJoin('t.category', 'c')
            ->groupBy('c.name')
            ->getQuery()
            ->getResult();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_category' => $byCategory,
        ];
    }
}
