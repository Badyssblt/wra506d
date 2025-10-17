<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TicketVoter extends Voter
{
    public const EDIT = 'TICKET_EDIT';
    public const VIEW = 'TICKET_VIEW';
    public const DELETE = 'TICKET_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($ticket, $user),
            self::EDIT => $this->canEdit($ticket, $user),
            self::DELETE => $this->canDelete($ticket, $user),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        // Users can view their own tickets or tickets they're assigned to
        // Agents and admins can view all tickets
        if (in_array('ROLE_AGENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return $ticket->getCreator() === $user || $ticket->getAgent()->contains($user);
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
        // Agents and admins can edit all tickets
        if (in_array('ROLE_AGENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Users can only edit their own tickets
        return $ticket->getCreator() === $user;
    }

    private function canDelete(Ticket $ticket, User $user): bool
    {
        // Only admins can delete tickets
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
