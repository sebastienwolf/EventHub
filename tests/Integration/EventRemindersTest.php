<?php

namespace App\Tests\Integration;

use App\Factory\ConferenceFactory;
use App\Factory\RegistrationFactory;
use App\Factory\UserFactory;
use App\Message\DispatchEventReminders;
use App\Message\SendEventReminder;
use App\MessageHandler\DispatchEventRemindersHandler;
use App\MessageHandler\SendEventReminderHandler;
use App\Scheduler\ReminderSchedule;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class EventRemindersTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testRemindersAreQueuedOnceForEventsStartingWithin24Hours(): void
    {
        $soon = ConferenceFactory::new()->published()->startingAt('+10 hours')->create();
        RegistrationFactory::createMany(2, ['event' => $soon]);

        $later = ConferenceFactory::new()->published()->startingAt('+3 days')->create();
        RegistrationFactory::createOne(['event' => $later]);

        $draft = ConferenceFactory::new()->startingAt('+5 hours')->create();

        $handler = static::getContainer()->get(DispatchEventRemindersHandler::class);

        self::assertSame(2, $handler(new DispatchEventReminders()));
        self::assertCount(2, $this->asyncTransport()->getSent());
        self::assertInstanceOf(SendEventReminder::class, $this->asyncTransport()->getSent()[0]->getMessage());
        self::assertNotNull($soon->getReminderSentAt());
        self::assertNull($later->getReminderSentAt());
        self::assertNull($draft->getReminderSentAt());

        self::assertSame(0, $handler(new DispatchEventReminders()), 'Reminders are sent only once');
    }

    public function testReminderEmailIsLocalized(): void
    {
        $event = ConferenceFactory::new()->published()->startingAt('+10 hours', '+90 minutes')->create(['title' => 'Symfony Live']);
        $registration = RegistrationFactory::createOne([
            'event' => $event,
            'participant' => UserFactory::new()->create(['locale' => 'fr']),
        ]);

        static::getContainer()->get(SendEventReminderHandler::class)(new SendEventReminder($registration->getId()));

        self::assertQueuedEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailSubjectContains($email, 'Rappel : Symfony Live');
        self::assertEmailTextBodyContains($email, 'Durée : 1 h 30');
    }

    public function testScheduleRunsTheReminderTaskEvery15Minutes(): void
    {
        $schedule = static::getContainer()->get(ReminderSchedule::class)->getSchedule();
        $messages = $schedule->getRecurringMessages();

        self::assertCount(1, $messages);
        self::assertStringContainsString('every 15 minutes', (string) $messages[0]->getTrigger());
    }

    private function asyncTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');

        return $transport;
    }
}
