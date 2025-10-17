<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TicketStatsController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $stats = $this->ticketRepository->getTicketStatistics();

        return $this->json([
            'total' => $stats['total'] ?? 0,
            'by_status' => $stats['by_status'] ?? [],
            'by_priority' => $stats['by_priority'] ?? [],
            'by_category' => $stats['by_category'] ?? [],
        ]);
    }
}
