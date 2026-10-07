<?php

namespace App\EventListener;

use App\DomainEvent\ParticipantRegistered;
use App\Notification\NewRegistrationNotification;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Second listener of the same domain event: tells the organizer someone registered.
 */
#[AsEventListener]
final class NotifyOrganizerListener
{
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(ParticipantRegistered $event): void
    {
        $registration = $event->registration;
        $organizer = $registration->getEvent()->getOrganizer();
        if (null === $organizer) {
            return;
        }

        $subject = $this->translator->trans('email.new_registration.subject', [
            '%title%' => $registration->getEvent()->getTitle(),
        ], locale: $organizer->getLocale());

        $this->notifier->send(
            new NewRegistrationNotification($registration, $subject),
            new Recipient((string) $organizer->getEmail()),
        );
    }
}
