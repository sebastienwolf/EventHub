<?php

namespace App\Service;

use App\Entity\Event;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;

/**
 * The idiomatic Symfony "helper": a service with its dependencies injected,
 * usable from PHP and, thanks to #[AsTwigFilter], from Twig: {{ event|duration }}.
 */
final class DurationFormatter
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * 90 -> "1 h 30 min", 120 -> "2 h", 45 -> "45 min" (translated).
     */
    public function formatMinutes(int $minutes, ?string $locale = null): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        $key = match (true) {
            0 === $hours => 'duration.minutes',
            0 === $rest => 'duration.hours',
            default => 'duration.hours_minutes',
        };

        return $this->translator->trans($key, ['%hours%' => $hours, '%minutes%' => $rest], locale: $locale);
    }

    #[AsTwigFilter('duration')]
    public function formatEvent(Event $event, ?string $locale = null): string
    {
        return $this->formatMinutes($event->getDurationInMinutes(), $locale);
    }
}
