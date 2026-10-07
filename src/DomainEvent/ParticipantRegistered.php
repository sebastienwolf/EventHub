<?php

namespace App\DomainEvent;

use App\Entity\Registration;

/**
 * Laravel "Event" equivalent: dispatched through the EventDispatcher once a
 * registration is saved. Listeners react to it (#[AsEventListener]) without the
 * RegistrationManager knowing about emails or notifications.
 */
final class ParticipantRegistered
{
    public function __construct(
        public readonly Registration $registration,
    ) {
    }
}
