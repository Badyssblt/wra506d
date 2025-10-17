<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\TicketStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ChangeTicketStatusController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(Ticket $ticket, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['status'])) {
            return $this->json(['error' => 'Status is required'], 400);
        }

        $newStatus = TicketStatusEnum::tryFrom($data['status']);

        if (!$newStatus) {
            return $this->json(['error' => 'Invalid status value'], 400);
        }

        $ticket->setStatus($newStatus);
        $this->entityManager->flush();

        return $this->json([
            'id' => $ticket->getId(),
            'status' => $ticket->getStatus()->value,
            'message' => 'Ticket status updated successfully'
        ]);
    }
}
