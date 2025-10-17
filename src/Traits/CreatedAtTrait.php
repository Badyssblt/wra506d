<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait
{
    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    public ?\DateTimeImmutable $createdAt = null;

}
