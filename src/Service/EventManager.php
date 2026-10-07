<?php

namespace App\Service;

use App\Dto\CreateEventRequest;
use App\Dto\UpdateEventRequest;
use App\Entity\Conference;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\Workshop;
use App\Enum\EventStatus;
use App\Exception\InvalidEventStateException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application service holding the event use cases, so controllers stay thin.
 */
final class EventManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function create(CreateEventRequest $request, User $organizer): Event
    {
        $event = match ($request->type) {
            Conference::TYPE => (new Conference())->setSpeakers($request->speakers),
            Workshop::TYPE => (new Workshop())
                ->setLevel($request->level)
                ->setPrerequisites($request->prerequisites),
        };

        $event
            ->setTitle($request->title)
            ->setDescription($request->description)
            ->setStartsAt($request->startsAt)
            ->setEndsAt($request->endsAt)
            ->setLocation($request->location)
            ->setCapacity($request->capacity)
            ->setOrganizer($organizer);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    public function update(Event $event, UpdateEventRequest $request): Event
    {
        if (EventStatus::Cancelled === $event->getStatus()) {
            throw InvalidEventStateException::cannotEditCancelled();
        }

        null !== $request->title && $event->setTitle($request->title);
        null !== $request->description && $event->setDescription($request->description);
        null !== $request->startsAt && $event->setStartsAt($request->startsAt);
        null !== $request->endsAt && $event->setEndsAt($request->endsAt);
        null !== $request->location && $event->setLocation($request->location);

        if (null !== $request->capacity) {
            if ($request->capacity < $event->getRegisteredCount()) {
                throw InvalidEventStateException::capacityBelowRegistrations($event->getRegisteredCount());
            }
            $event->setCapacity($request->capacity);
        }

        if ($event instanceof Conference && null !== $request->speakers) {
            $event->setSpeakers($request->speakers);
        }

        if ($event instanceof Workshop) {
            null !== $request->level && $event->setLevel($request->level);
            null !== $request->prerequisites && $event->setPrerequisites($request->prerequisites);
        }

        // Dates may come from the request or from the entity: check them together
        if ($event->getEndsAt() <= $event->getStartsAt()) {
            throw InvalidEventStateException::endsBeforeStart();
        }

        $this->entityManager->flush();

        return $event;
    }

    public function publish(Event $event): Event
    {
        if (EventStatus::Draft !== $event->getStatus()) {
            throw InvalidEventStateException::cannotPublish();
        }

        $event->setStatus(EventStatus::Published);
        $this->entityManager->flush();

        return $event;
    }

    public function cancel(Event $event): Event
    {
        if (EventStatus::Cancelled === $event->getStatus()) {
            throw InvalidEventStateException::cannotCancel();
        }

        $event->setStatus(EventStatus::Cancelled);
        $this->entityManager->flush();

        return $event;
    }

    public function delete(Event $event): void
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }
}
