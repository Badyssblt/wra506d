<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Uuid;

#[AsController]
class AssignAgentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(Ticket $ticket, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['agentId'])) {
            return $this->json(['error' => 'Agent ID is required'], 400);
        }

        try {
            $agentUuid = Uuid::fromString($data['agentId']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid UUID format for agent ID'], 400);
        }

        $agent = $this->userRepository->find($agentUuid);

        if (!$agent) {
            return $this->json(['error' => 'Agent not found'], 404);
        }

        // Check if agent has ROLE_AGENT or ROLE_ADMIN
        if (!in_array('ROLE_AGENT', $agent->getRoles()) && !in_array('ROLE_ADMIN', $agent->getRoles())) {
            return $this->json(['error' => 'User is not an agent'], 400);
        }

        $ticket->addAgent($agent);
        $this->entityManager->flush();

        return $this->json([
            'id' => $ticket->getId(),
            'message' => 'Agent assigned successfully',
            'agents' => array_map(fn($a) => [
                'id' => $a->getId(),
                'name' => $a->getName(),
                'email' => $a->getEmail()
            ], $ticket->getAgent()->toArray())
        ]);
    }
}
