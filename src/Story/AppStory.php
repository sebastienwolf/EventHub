<?php

namespace App\Story;

use App\Factory\ConferenceFactory;
use App\Factory\RegistrationFactory;
use App\Factory\UserFactory;
use App\Factory\WorkshopFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

/**
 * Laravel DatabaseSeeder equivalent: a realistic demo dataset.
 * Every account uses the password "password".
 */
#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        UserFactory::new()->admin()->create([
            'email' => 'admin@eventhub.test',
            'firstName' => 'Admin',
            'lastName' => 'EventHub',
            'locale' => 'en',
        ]);

        $organizer = UserFactory::new()->organizer()->create([
            'email' => 'organizer@eventhub.test',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'locale' => 'fr',
        ]);
        $organizers = [$organizer, ...UserFactory::new()->organizer()->many(2)->create()];

        $participant = UserFactory::createOne([
            'email' => 'participant@eventhub.test',
            'firstName' => 'Alan',
            'lastName' => 'Turing',
            'locale' => 'fr',
        ]);
        $participants = [$participant, ...UserFactory::createMany(20)];

        $events = [
            ...ConferenceFactory::new()->published()->many(8)->create(fn () => ['organizer' => $organizers[array_rand($organizers)]]),
            ...WorkshopFactory::new()->published()->many(6)->create(fn () => ['organizer' => $organizers[array_rand($organizers)]]),
            // Starts tomorrow: try "bin/console app:reminders:send"
            WorkshopFactory::new()->published()->startingAt('+20 hours', '+3 hours')->create([
                'title' => 'Symfony for Laravel developers',
                'organizer' => $organizer,
                'capacity' => 12,
            ]),
        ];

        // A few drafts and a cancelled event, only visible to their organizer
        ConferenceFactory::new()->many(2)->create(['organizer' => $organizer]);
        WorkshopFactory::new()->cancelled()->create(['organizer' => $organizer]);

        foreach ($events as $event) {
            shuffle($participants);
            $count = min(random_int(0, 8), $event->getCapacity() ?? 8);

            foreach (\array_slice($participants, 0, $count) as $user) {
                RegistrationFactory::createOne(['event' => $event, 'participant' => $user]);
            }
        }
    }
}
