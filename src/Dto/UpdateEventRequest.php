<?php

namespace App\Dto;

use App\Enum\WorkshopLevel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PATCH semantics: only the provided (non-null) fields are applied.
 * The event type cannot be changed.
 */
final class UpdateEventRequest
{
    /**
     * @param list<string>|null $speakers
     */
    public function __construct(
        #[Assert\Length(min: 3, max: 150)]
        #[Assert\NotBlank(allowNull: true)]
        public readonly ?string $title = null,

        #[Assert\NotBlank(allowNull: true)]
        public readonly ?string $description = null,

        #[Assert\GreaterThan('now')]
        public readonly ?\DateTimeImmutable $startsAt = null,

        public readonly ?\DateTimeImmutable $endsAt = null,

        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public readonly ?string $location = null,

        #[Assert\Positive]
        public readonly ?int $capacity = null,

        #[Assert\All([new Assert\NotBlank(), new Assert\Length(max: 100)])]
        public readonly ?array $speakers = null,

        public readonly ?WorkshopLevel $level = null,

        public readonly ?string $prerequisites = null,
    ) {
    }
}
