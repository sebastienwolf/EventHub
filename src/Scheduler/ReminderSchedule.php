<?php

namespace App\Scheduler;

use App\Message\DispatchEventReminders;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Laravel task scheduling equivalent (no cron entry per task needed).
 *
 * Run the worker: php bin/console messenger:consume scheduler_reminders async
 */
#[AsSchedule('reminders')]
final class ReminderSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::every('15 minutes', new DispatchEventReminders()))
            ->stateful($this->cache) // ensure missed tasks are executed after a worker restart
            ->processOnlyLastMissedRun(true); // but only once
    }
}
