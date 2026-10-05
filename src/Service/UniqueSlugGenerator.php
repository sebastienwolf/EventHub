<?php

namespace App\Service;

use App\Repository\EventRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Turns an event title into a URL-friendly slug that is unique in the database:
 * "Symfony Live" -> "symfony-live", then "symfony-live-2", "symfony-live-3"...
 */
final class UniqueSlugGenerator
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function generate(string $title, ?int $excludeId = null): string
    {
        $base = $this->slugger->slug($title)->lower()->truncate(170)->trim('-')->toString();
        $base = '' !== $base ? $base : 'event';

        $slug = $base;
        for ($i = 2; $this->eventRepository->slugExists($slug, $excludeId); ++$i) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
