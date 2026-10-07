<?php

namespace App\MessageHandler;

use App\Message\SendRegistrationConfirmation;
use App\Repository\RegistrationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final class SendRegistrationConfirmationHandler
{
    public function __construct(
        private readonly RegistrationRepository $registrationRepository,
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(SendRegistrationConfirmation $message): void
    {
        $registration = $this->registrationRepository->find($message->registrationId);
        if (null === $registration) {
            // Cancelled in the meantime: nothing to confirm
            return;
        }

        $participant = $registration->getParticipant();
        $event = $registration->getEvent();
        $locale = $participant->getLocale();

        // Laravel Mailable equivalent: a Twig template rendered in the participant's language
        $email = (new TemplatedEmail())
            ->to(new Address((string) $participant->getEmail(), $participant->getFullName()))
            ->subject($this->translator->trans('email.registration.subject', ['%title%' => $event->getTitle()], locale: $locale))
            ->htmlTemplate('emails/registration_confirmation.html.twig')
            ->textTemplate('emails/registration_confirmation.txt.twig')
            ->locale($locale)
            ->context([
                'participant' => $participant,
                'event' => $event,
            ]);

        $this->mailer->send($email);
    }
}
