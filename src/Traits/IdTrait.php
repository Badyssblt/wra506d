<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait IdTrait
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    #[ORM\PrePersist]
    public function initializeUuid(): void
    {
        if ($this->id === null) {
            $this->id = Uuid::v4();
        }
    }
}
