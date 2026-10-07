<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * The requested change is not compatible with the current state of the event.
 */
final class InvalidEventStateException extends BusinessRuleException
{
    public static function cannotPublish(): self
    {
        return new self('event.error.cannot_publish');
    }

    public static function cannotCancel(): self
    {
        return new self('event.error.cannot_cancel');
    }

    public static function cannotEditCancelled(): self
    {
        return new self('event.error.cannot_edit_cancelled');
    }

    public static function endsBeforeStart(): self
    {
        return new self('event.error.ends_before_start', [], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function capacityBelowRegistrations(int $registered): self
    {
        return new self('event.error.capacity_below_registrations', ['%count%' => $registered], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
