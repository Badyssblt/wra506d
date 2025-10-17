<?php

namespace App\Entity;

use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use App\Repository\TicketRepository;
use App\Traits\CreatedAtTrait;
use App\Traits\IdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    use IdTrait;
    use CreatedAtTrait;

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    public ?string $description = null;


    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    public ?User $creator = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'tickets_agent')]
    public Collection $agent;

    #[ORM\Column(type: 'string', enumType: TicketPriorityEnum::class)]
    public TicketPriorityEnum $priority = TicketPriorityEnum::LOW;

    #[ORM\Column(type: 'string', enumType: TicketStatusEnum::class)]
    public TicketStatusEnum $status = TicketStatusEnum::OPEN;

    public function __construct()
    {
        $this->agent = new ArrayCollection();
    }


}
