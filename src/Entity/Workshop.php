<?php

namespace App\Entity;

use App\Enum\WorkshopLevel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class Workshop extends Event
{
    public const TYPE = 'workshop';

    /** Nullable at the database level because the column is shared with conferences (STI). */
    #[ORM\Column(length: 20, nullable: true, enumType: WorkshopLevel::class)]
    #[Groups(['event:list', 'event:read'])]
    private ?WorkshopLevel $level = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['event:read'])]
    private ?string $prerequisites = null;

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLevel(): ?WorkshopLevel
    {
        return $this->level;
    }

    public function setLevel(WorkshopLevel $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getPrerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(?string $prerequisites): static
    {
        $this->prerequisites = $prerequisites;

        return $this;
    }
}
