<?php

namespace App\Tests\Functional;

use App\Entity\Event;
use App\Factory\ConferenceFactory;
use App\Factory\RegistrationFactory;
use App\Factory\UserFactory;
use App\Factory\WorkshopFactory;

final class EventApiTest extends ApiTestCase
{
    public function testCatalogListsOnlyUpcomingPublishedEvents(): void
    {
        ConferenceFactory::new()->published()->many(2)->create();
        WorkshopFactory::new()->published()->create();
        ConferenceFactory::createOne(); // draft
        WorkshopFactory::new()->cancelled()->create();

        $data = $this->request('GET', '/api/events');

        self::assertResponseIsSuccessful();
        self::assertSame(3, $data['meta']['total']);
        self::assertSame(['page' => 1, 'limit' => 20, 'total' => 3, 'pages' => 1], $data['meta']);
        self::assertArrayNotHasKey('email', $data['data'][0]['organizer'], 'The organizer is serialized with public fields only');
    }

    public function testCatalogCanBeFilteredByTypeAndPaginated(): void
    {
        ConferenceFactory::new()->published()->create();
        WorkshopFactory::new()->published()->many(3)->create();

        $data = $this->request('GET', '/api/events?type=workshop&limit=2&page=2');

        self::assertResponseIsSuccessful();
        self::assertSame(['page' => 2, 'limit' => 2, 'total' => 3, 'pages' => 2], $data['meta']);
        self::assertCount(1, $data['data']);
        self::assertSame('workshop', $data['data'][0]['type']);
    }

    public function testInvalidCatalogFilterReturns422(): void
    {
        $this->request('GET', '/api/events?type=party');

        self::assertResponseStatusCodeSame(422);
    }

    public function testShowFindsEventBySlug(): void
    {
        ConferenceFactory::new()->published()->create(['title' => 'Symfony Live Brussels', 'speakers' => ['Fabien']]);

        $data = $this->request('GET', '/api/events/symfony-live-brussels');

        self::assertResponseIsSuccessful();
        self::assertSame('conference', $data['type']);
        self::assertSame(['Fabien'], $data['speakers']);
    }

    public function testDraftEventIsHiddenFromOtherUsers(): void
    {
        ConferenceFactory::createOne(['title' => 'Secret draft']);

        $this->request('GET', '/api/events/secret-draft', as: UserFactory::createOne());

        self::assertResponseStatusCodeSame(403);
    }

    public function testOrganizerCreatesAWorkshopWithGeneratedSlugAndTimestamps(): void
    {
        $organizer = UserFactory::new()->organizer()->create();
        WorkshopFactory::createOne(['title' => 'Découvrir Symfony']);

        $data = $this->request('POST', '/api/events', $this->workshopPayload(['title' => 'Découvrir Symfony']), $organizer);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('decouvrir-symfony-2', $data['slug'], 'Slugs are unique');
        self::assertSame('draft', $data['status']);
        self::assertSame('beginner', $data['level']);
        self::assertSame(180, $data['durationInMinutes']);
        self::assertSame('2030-06-01T07:00:00+00:00', $data['startsAt'], 'Dates are stored in UTC');
        self::assertNotNull($data['createdAt']);
        self::assertResponseHeaderSame('Location', '/api/events/decouvrir-symfony-2');
    }

    public function testWorkshopRequiresALevel(): void
    {
        $data = $this->request('POST', '/api/events', $this->workshopPayload(['level' => null]), UserFactory::new()->organizer()->create());

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['level'], array_column($data['error']['violations'], 'property'));
    }

    public function testParticipantCannotCreateEvents(): void
    {
        $this->request('POST', '/api/events', $this->workshopPayload(), UserFactory::createOne());

        self::assertResponseStatusCodeSame(403);
    }

    public function testOnlyTheOrganizerOrAnAdminCanUpdateAnEvent(): void
    {
        $event = ConferenceFactory::new()->published()->create(['title' => 'Original title']);
        $uri = '/api/events/'.$event->getId();

        $this->request('PATCH', $uri, ['title' => 'Hacked'], UserFactory::new()->organizer()->create());
        self::assertResponseStatusCodeSame(403);

        $data = $this->request('PATCH', $uri, ['title' => 'Renamed by owner'], $event->getOrganizer());
        self::assertResponseIsSuccessful();
        self::assertSame('renamed-by-owner', $data['slug'], 'The slug follows the title');

        $data = $this->request('PATCH', $uri, ['location' => 'Namur'], UserFactory::new()->admin()->create());
        self::assertResponseIsSuccessful();
        self::assertSame('Namur', $data['location']);
    }

    public function testPublishOnlyWorksOnDrafts(): void
    {
        $event = WorkshopFactory::createOne();
        $uri = \sprintf('/api/events/%d/publish', $event->getId());

        $data = $this->request('POST', $uri, as: $event->getOrganizer());
        self::assertResponseIsSuccessful();
        self::assertSame('published', $data['status']);

        $data = $this->request('POST', $uri, as: $event->getOrganizer(), locale: 'fr');
        self::assertResponseStatusCodeSame(409);
        self::assertSame('Seuls les événements en brouillon peuvent être publiés.', $data['error']['message']);
    }

    public function testCapacityCannotDropBelowRegistrations(): void
    {
        $event = ConferenceFactory::new()->published()->withCapacity(10)->create();
        $this->registerParticipants($event, 3);

        $data = $this->request('PATCH', '/api/events/'.$event->getId(), ['capacity' => 2], $event->getOrganizer());

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('(3)', $data['error']['message']);
    }

    public function testOrganizerDeletesEvent(): void
    {
        $event = ConferenceFactory::createOne();
        $id = $event->getId();

        $this->request('DELETE', '/api/events/'.$id, as: $event->getOrganizer());

        self::assertResponseStatusCodeSame(204);
        ConferenceFactory::assert()->notExists(['id' => $id]);
    }

    public function testMyEventsIncludesDrafts(): void
    {
        $organizer = UserFactory::new()->organizer()->create();
        ConferenceFactory::createOne(['organizer' => $organizer]);
        WorkshopFactory::new()->published()->create(['organizer' => $organizer]);
        ConferenceFactory::new()->published()->create();

        $data = $this->request('GET', '/api/me/events', as: $organizer);

        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['meta']['total']);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function workshopPayload(array $overrides = []): array
    {
        return [
            'type' => 'workshop',
            'title' => 'Symfony for Laravel developers',
            'description' => 'Hands-on workshop',
            'startsAt' => '2030-06-01T09:00:00+02:00',
            'endsAt' => '2030-06-01T12:00:00+02:00',
            'location' => 'Brussels',
            'capacity' => 12,
            'level' => 'beginner',
            ...$overrides,
        ];
    }

    private function registerParticipants(Event $event, int $count): void
    {
        foreach (UserFactory::createMany($count) as $participant) {
            RegistrationFactory::createOne(['event' => $event, 'participant' => $participant]);
        }
    }
}
