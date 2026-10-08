<?php

namespace App\MessageHandler;

use App\Message\SendEventReminder;
use App\Repository\RegistrationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final class SendEventReminderHandler
{
    public function __construct(
        private readonly RegistrationRepository $registrationRepository,
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(SendEventReminder $message): void
    {
        $registration = $this->registrationRepository->find($message->registrationId);
        if (null === $registration || !$registration->getEvent()->isPublished()) {
            return;
        }

        $participant = $registration->getParticipant();
        $event = $registration->getEvent();
        $locale = $participant->getLocale();

        $email = (new TemplatedEmail())
            ->to(new Address((string) $participant->getEmail(), $participant->getFullName()))
            ->subject($this->translator->trans('email.reminder.subject', ['%title%' => $event->getTitle()], locale: $locale))
            ->htmlTemplate('emails/event_reminder.html.twig')
            ->textTemplate('emails/event_reminder.txt.twig')
            ->locale($locale)
            ->context([
                'participant' => $participant,
                'event' => $event,
            ]);

        $this->mailer->send($email);
    }
}
