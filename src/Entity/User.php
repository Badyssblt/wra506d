<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Filter\UuidFilter;
use App\Repository\UserRepository;
use App\Traits\CreatedAtTrait;
use App\Traits\IdTrait;
use App\Traits\UpdatedAtTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER') and (object == user or is_granted('ROLE_ADMIN'))"),
        new GetCollection(security: "is_granted('ROLE_AGENT')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_USER') and (object == user or is_granted('ROLE_ADMIN'))"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact', 'name' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'name', 'email'])]
#[ApiFilter(UuidFilter::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdTrait;
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas valide")]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'creator')]
    private Collection $tickets;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\ManyToMany(targetEntity: Ticket::class, mappedBy: 'agent')]
    private Collection $tickets_agent;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->tickets_agent = new ArrayCollection();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setCreator($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getCreator() === $this) {
                $ticket->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTicketsAgent(): Collection
    {
        return $this->tickets_agent;
    }

    public function addTicketsAgent(Ticket $ticketsAgent): static
    {
        if (!$this->tickets_agent->contains($ticketsAgent)) {
            $this->tickets_agent->add($ticketsAgent);
            $ticketsAgent->addAgent($this);
        }

        return $this;
    }

    public function removeTicketsAgent(Ticket $ticketsAgent): static
    {
        if ($this->tickets_agent->removeElement($ticketsAgent)) {
            $ticketsAgent->removeAgent($this);
        }

        return $this;
    }
}
