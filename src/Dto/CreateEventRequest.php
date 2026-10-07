<?php

namespace App\Dto;

use App\Entity\Conference;
use App\Entity\Workshop;
use App\Enum\WorkshopLevel;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateEventRequest
{
    /**
     * @param list<string> $speakers
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [Conference::TYPE, Workshop::TYPE])]
        public readonly string $type = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 150)]
        public readonly string $title = '',

        #[Assert\NotBlank]
        public readonly string $description = '',

        #[Assert\NotNull]
        #[Assert\GreaterThan('now')]
        public readonly ?\DateTimeImmutable $startsAt = null,

        #[Assert\NotNull]
        #[Assert\GreaterThan(propertyPath: 'startsAt')]
        public readonly ?\DateTimeImmutable $endsAt = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $location = '',

        /** Null means unlimited seats. */
        #[Assert\Positive]
        public readonly ?int $capacity = null,

        /** Conference only. */
        #[Assert\All([new Assert\NotBlank(), new Assert\Length(max: 100)])]
        public readonly array $speakers = [],

        /** Workshop only, required for workshops. */
        #[Assert\When(
            expression: 'this.type === "workshop"',
            constraints: [new Assert\NotNull()],
        )]
        public readonly ?WorkshopLevel $level = null,

        /** Workshop only. */
        public readonly ?string $prerequisites = null,
    ) {
    }
}
