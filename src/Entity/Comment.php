<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use App\Filter\UuidFilter;
use App\Repository\CommentRepository;
use App\Traits\CreatedAtTrait;
use App\Traits\IdTrait;
use App\Traits\UpdatedAtTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_USER') and (object.getAuthor() == user or is_granted('ROLE_AGENT'))"),
        new Delete(security: "is_granted('ROLE_USER') and (object.getAuthor() == user or is_granted('ROLE_ADMIN'))")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['ticket' => 'exact', 'author' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(UuidFilter::class)]
class Comment
{
    use IdTrait;
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du commentaire est requis')]
    #[Assert\Length(min: 1, minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractère')]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le ticket est requis')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'auteur est requis")]
    private ?User $author = null;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }
}
