<?php

namespace App\Dto;

use App\Entity\Conference;
use App\Entity\Workshop;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Catalog filters, mapped from the query string: ?type=workshop.
 */
final class EventFilterQuery
{
    public function __construct(
        #[Assert\Choice(choices: [Conference::TYPE, Workshop::TYPE])]
        public readonly ?string $type = null,
    ) {
    }
}
