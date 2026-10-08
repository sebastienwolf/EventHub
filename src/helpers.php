<?php

/*
 * Global helper functions, loaded by Composer ("autoload.files" in composer.json).
 *
 * This mirrors Laravel's helpers.php. In Symfony, prefer an injectable service
 * (see App\Service\DurationFormatter): keep this file for small, pure,
 * dependency-free functions only.
 */

if (!function_exists('minutes_between')) {
    /**
     * Whole minutes between two dates, never negative.
     */
    function minutes_between(DateTimeInterface $start, DateTimeInterface $end): int
    {
        return max(0, intdiv($end->getTimestamp() - $start->getTimestamp(), 60));
    }
}

if (!function_exists('str_initials')) {
    /**
     * "Ada Lovelace" -> "AL" (used as an avatar placeholder by API clients).
     */
    function str_initials(string $name, int $max = 2): string
    {
        $words = preg_split('/[\s\-]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = array_map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)), $words);

        return implode('', array_slice($initials, 0, $max));
    }
}
