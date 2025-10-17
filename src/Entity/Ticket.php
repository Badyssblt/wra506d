<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\TicketStatsController;
use App\Controller\ChangeTicketStatusController;
use App\Controller\AssignAgentController;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use App\Filter\UuidFilter;
use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use App\Repository\TicketRepository;
use App\Traits\CreatedAtTrait;
use App\Traits\IdTrait;
use App\Traits\UpdatedAtTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_USER') and (object.getCreator() == user or is_granted('ROLE_AGENT'))"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        new Get(
            uriTemplate: '/tickets/stats',
            controller: TicketStatsController::class,
            name: 'ticket_stats',
            security: "is_granted('ROLE_AGENT')"
        ),
        new Patch(
            uriTemplate: '/tickets/{id}/status',
            controller: ChangeTicketStatusController::class,
            name: 'change_ticket_status',
            security: "is_granted('ROLE_AGENT')"
        ),
        new Post(
            uriTemplate: '/tickets/{id}/assign',
            controller: AssignAgentController::class,
            name: 'assign_agent',
            security: "is_granted('ROLE_AGENT')"
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', 'description' => 'partial', 'status' => 'exact', 'priority' => 'exact', 'creator' => 'exact', 'category' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'updatedAt', 'priority', 'status'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt', 'updatedAt'])]
#[ApiFilter(UuidFilter::class)]
class Ticket
{
    use IdTrait;
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    public ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est requise')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères')]
    public ?string $description = null;


    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le créateur est requis')]
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

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: true)]
    public ?Category $category = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ticket', cascade: ['persist', 'remove'])]
    public Collection $comments;

    public function __construct()
    {
        $this->agent = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAgent(): Collection
    {
        return $this->agent;
    }

    public function addAgent(User $agent): static
    {
        if (!$this->agent->contains($agent)) {
            $this->agent->add($agent);
        }

        return $this;
    }

    public function removeAgent(User $agent): static
    {
        $this->agent->removeElement($agent);

        return $this;
    }

    public function setPriority(TicketPriorityEnum $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority(): TicketPriorityEnum
    {
        return $this->priority;
    }

    public function setStatus(TicketStatusEnum $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): TicketStatusEnum
    {
        return $this->status;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTicket($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTicket() === $this) {
                $comment->setTicket(null);
            }
        }

        return $this;
    }


}
