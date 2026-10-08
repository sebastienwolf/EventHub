<?php

namespace App\Message;

/**
 * Triggered every 15 minutes by App\Scheduler\ReminderSchedule
 * (or manually with "bin/console app:reminders:send").
 */
final class DispatchEventReminders
{
    /** Reminders are sent to events starting within this window. */
    public const WINDOW = '+24 hours';
}
