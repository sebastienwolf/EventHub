<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class RegistrationException extends BusinessRuleException
{
    public static function eventFull(): self
    {
        return new self('registration.error.event_full');
    }

    public static function alreadyRegistered(): self
    {
        return new self('registration.error.already_registered');
    }

    public static function eventNotOpen(): self
    {
        return new self('registration.error.event_not_open');
    }

    public static function notRegistered(): self
    {
        return new self('registration.error.not_registered', [], Response::HTTP_NOT_FOUND);
    }
}
