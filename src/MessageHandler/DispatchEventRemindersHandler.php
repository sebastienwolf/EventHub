<?php

namespace App\MessageHandler;

use App\Message\DispatchEventReminders;
use App\Message\SendEventReminder;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Fans out one SendEventReminder message per participant, then flags the event
 * so that reminders are sent only once.
 */
#[AsMessageHandler]
final class DispatchEventRemindersHandler
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return int number of reminders queued
     */
    public function __invoke(DispatchEventReminders $message): int
    {
        $now = $this->clock->now();
        $queued = 0;

        foreach ($this->eventRepository->findEventsNeedingReminder($now, $now->modify(DispatchEventReminders::WINDOW)) as $event) {
            foreach ($event->getRegistrations() as $registration) {
                $this->bus->dispatch(new SendEventReminder((int) $registration->getId()));
                ++$queued;
            }

            $event->setReminderSentAt($now);
        }

        $this->entityManager->flush();
        $this->logger->info('{count} event reminder(s) queued.', ['count' => $queued]);

        return $queued;
    }
}
