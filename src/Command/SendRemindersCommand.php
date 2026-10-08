<?php

namespace App\Command;

use App\Message\DispatchEventReminders;
use App\MessageHandler\DispatchEventRemindersHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs the reminder task immediately, without waiting for the scheduler.
 */
#[AsCommand(name: 'app:reminders:send', description: 'Queue reminder emails for events starting within 24 hours')]
final class SendRemindersCommand
{
    public function __construct(
        private readonly DispatchEventRemindersHandler $handler,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $count = ($this->handler)(new DispatchEventReminders());

        $io->success(\sprintf('%d reminder(s) queued. Run "messenger:consume async" to send them.', $count));

        return Command::SUCCESS;
    }
}
