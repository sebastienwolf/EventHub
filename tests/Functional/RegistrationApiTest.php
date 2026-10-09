<?php

namespace App\Tests\Functional;

use App\Factory\ConferenceFactory;
use App\Factory\RegistrationFactory;
use App\Factory\UserFactory;
use App\Factory\WorkshopFactory;
use App\Message\SendRegistrationConfirmation;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

final class RegistrationApiTest extends ApiTestCase
{
    public function testParticipantRegistersAndSideEffectsAreQueued(): void
    {
        $organizer = UserFactory::new()->organizer()->create(['email' => 'organizer@example.com']);
        $event = WorkshopFactory::new()->published()->withCapacity(5)->create(['organizer' => $organizer]);
        $participant = UserFactory::createOne();

        $data = $this->request('POST', \sprintf('/api/events/%d/registrations', $event->getId()), as: $participant);

        self::assertResponseStatusCodeSame(201);
        self::assertSame($event->getId(), $data['event']['id']);
        self::assertSame(4, $data['event']['remainingSeats']);
        self::assertSame($participant->getFullName(), $data['participant']['fullName']);
        RegistrationFactory::assert()->count(1);

        // Listener 1: the confirmation email is queued as a message (sent by the worker)
        $confirmations = $this->queuedMessages(SendRegistrationConfirmation::class);
        self::assertCount(1, $confirmations);
        self::assertSame($data['id'], $confirmations[0]->registrationId);

        // Listener 2: the organizer is notified by email through the Notifier
        $emails = $this->queuedMessages(SendEmailMessage::class);
        self::assertCount(1, $emails);
        self::assertSame('organizer@example.com', $emails[0]->getMessage()->getTo()[0]->getAddress());
    }

    public function testCannotRegisterTwice(): void
    {
        $registration = RegistrationFactory::createOne();

        $data = $this->request('POST', \sprintf('/api/events/%d/registrations', $registration->getEvent()->getId()), as: $registration->getParticipant(), locale: 'fr');

        self::assertResponseStatusCodeSame(409);
        self::assertSame('Vous êtes déjà inscrit à cet événement.', $data['error']['message']);
    }

    public function testCannotRegisterToAFullEvent(): void
    {
        $event = ConferenceFactory::new()->published()->withCapacity(1)->create();
        RegistrationFactory::createOne(['event' => $event]);

        $data = $this->request('POST', \sprintf('/api/events/%d/registrations', $event->getId()), as: UserFactory::createOne());

        self::assertResponseStatusCodeSame(409);
        self::assertSame('This event is full.', $data['error']['message']);
    }

    public function testCannotRegisterToADraftOrPastEvent(): void
    {
        $draft = ConferenceFactory::createOne();
        $past = ConferenceFactory::new()->published()->startingAt('-1 day')->create();

        foreach ([$draft, $past] as $event) {
            $this->request('POST', \sprintf('/api/events/%d/registrations', $event->getId()), as: UserFactory::createOne());
            self::assertResponseStatusCodeSame(409);
        }

        self::assertSame([], $this->queuedMessages());
    }

    public function testParticipantUnregisters(): void
    {
        $registration = RegistrationFactory::createOne();
        $uri = \sprintf('/api/events/%d/registrations', $registration->getEvent()->getId());

        $this->request('DELETE', $uri, as: $registration->getParticipant());
        self::assertResponseStatusCodeSame(204);
        RegistrationFactory::assert()->empty();

        $this->request('DELETE', $uri, as: $registration->getParticipant());
        self::assertResponseStatusCodeSame(404);
    }

    public function testOnlyTheOrganizerSeesTheParticipants(): void
    {
        $event = ConferenceFactory::new()->published()->create();
        RegistrationFactory::createMany(3, ['event' => $event]);
        $uri = \sprintf('/api/events/%d/registrations', $event->getId());

        $this->request('GET', $uri, as: UserFactory::createOne());
        self::assertResponseStatusCodeSame(403);

        $data = $this->request('GET', $uri, as: $event->getOrganizer());
        self::assertResponseIsSuccessful();
        self::assertSame(3, $data['meta']['total']);
    }

    public function testMyRegistrations(): void
    {
        $participant = UserFactory::createOne();
        RegistrationFactory::createMany(2, ['participant' => $participant]);
        RegistrationFactory::createOne();

        $data = $this->request('GET', '/api/me/registrations', as: $participant);

        self::assertResponseIsSuccessful();
        self::assertSame(2, $data['meta']['total']);
    }
}
