<?php

namespace App\EventListener;

use App\DomainEvent\ParticipantRegistered;
use App\Message\SendRegistrationConfirmation;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Queues the confirmation email instead of sending it during the HTTP request.
 */
#[AsEventListener]
final class SendRegistrationConfirmationListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(ParticipantRegistered $event): void
    {
        $this->bus->dispatch(new SendRegistrationConfirmation((int) $event->registration->getId()));
    }
}
