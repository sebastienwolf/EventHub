<?php

namespace App\Notification;

use App\Entity\Registration;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

/**
 * Laravel Notification equivalent. The channels are chosen by the importance
 * (see channel_policy in config/packages/notifier.yaml): adding SMS or Slack
 * later only requires a transport, not a code change here.
 */
final class NewRegistrationNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private readonly Registration $registration,
        string $subject,
    ) {
        parent::__construct($subject);
        $this->importance(self::IMPORTANCE_LOW);
    }

    /**
     * Customizes the email channel with our own Twig template.
     */
    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): ?EmailMessage
    {
        $organizer = $this->registration->getEvent()->getOrganizer();

        $email = (new TemplatedEmail())
            ->to($recipient->getEmail())
            ->subject($this->getSubject())
            ->htmlTemplate('emails/new_registration.html.twig')
            ->textTemplate('emails/new_registration.txt.twig')
            ->locale($organizer?->getLocale() ?? 'en')
            ->context([
                'organizer' => $organizer,
                'participant' => $this->registration->getParticipant(),
                'event' => $this->registration->getEvent(),
            ]);

        return new EmailMessage($email);
    }
}
