<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class Conference extends Event
{
    public const TYPE = 'conference';

    /**
     * Nullable at the database level because the column is shared with workshops (STI).
     *
     * @var list<string>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $speakers = [];

    public function getType(): string
    {
        return self::TYPE;
    }

    /** @return list<string> */
    #[Groups(['event:read'])]
    public function getSpeakers(): array
    {
        return $this->speakers ?? [];
    }

    /** @param list<string> $speakers */
    public function setSpeakers(array $speakers): static
    {
        $this->speakers = array_values($speakers);

        return $this;
    }
}
