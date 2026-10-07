<?php

namespace App\Controller;

use App\Dto\CreateEventRequest;
use App\Dto\EventFilterQuery;
use App\Dto\PaginationQuery;
use App\Dto\UpdateEventRequest;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Security\Voter\EventVoter;
use App\Service\EventManager;
use Psr\Clock\ClockInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class EventController extends AbstractApiController
{
    private const LIST_GROUPS = ['event:list'];
    private const DETAIL_GROUPS = ['event:read'];

    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EventManager $eventManager,
    ) {
    }

    /**
     * Public catalog of upcoming published events: GET /api/events?type=workshop&page=1&limit=20.
     */
    #[Route('/events', name: 'api_events_list', methods: ['GET'])]
    public function list(
        ClockInterface $clock,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] EventFilterQuery $filter = new EventFilterQuery(),
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] PaginationQuery $pagination = new PaginationQuery(),
    ): JsonResponse {
        $queryBuilder = $this->eventRepository->createUpcomingPublishedQueryBuilder($clock->now(), $filter->type);

        return $this->paginate($queryBuilder, $pagination, self::LIST_GROUPS);
    }

    /**
     * Every event of the current organizer, whatever its status.
     */
    #[Route('/me/events', name: 'api_my_events', methods: ['GET'])]
    #[IsGranted(User::ROLE_ORGANIZER)]
    public function mine(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] PaginationQuery $pagination = new PaginationQuery()): JsonResponse
    {
        $queryBuilder = $this->eventRepository->createByOrganizerQueryBuilder($this->getAuthenticatedUser());

        return $this->paginate($queryBuilder, $pagination, self::LIST_GROUPS);
    }

    /**
     * Route model binding equivalent: #[MapEntity] fetches the event by its slug (404 if not found).
     */
    #[Route('/events/{slug}', name: 'api_events_show', requirements: ['slug' => Requirement::ASCII_SLUG], methods: ['GET'])]
    #[IsGranted(EventVoter::VIEW, subject: 'event')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Event $event): JsonResponse
    {
        return $this->ok($event, self::DETAIL_GROUPS);
    }

    #[Route('/events', name: 'api_events_create', methods: ['POST'])]
    #[IsGranted(User::ROLE_ORGANIZER)]
    public function create(#[MapRequestPayload] CreateEventRequest $payload): JsonResponse
    {
        $event = $this->eventManager->create($payload, $this->getAuthenticatedUser());

        return $this->created(
            $event,
            self::DETAIL_GROUPS,
            $this->generateUrl('api_events_show', ['slug' => $event->getSlug()]),
        );
    }

    /**
     * Without #[MapEntity], the {id} route parameter is resolved to the entity automatically.
     */
    #[Route('/events/{id}', name: 'api_events_update', requirements: ['id' => Requirement::DIGITS], methods: ['PATCH'])]
    #[IsGranted(EventVoter::EDIT, subject: 'event')]
    public function update(Event $event, #[MapRequestPayload] UpdateEventRequest $payload): JsonResponse
    {
        return $this->ok($this->eventManager->update($event, $payload), self::DETAIL_GROUPS);
    }

    #[Route('/events/{id}/publish', name: 'api_events_publish', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(EventVoter::EDIT, subject: 'event')]
    public function publish(Event $event): JsonResponse
    {
        return $this->ok($this->eventManager->publish($event), self::DETAIL_GROUPS);
    }

    #[Route('/events/{id}/cancel', name: 'api_events_cancel', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(EventVoter::EDIT, subject: 'event')]
    public function cancel(Event $event): JsonResponse
    {
        return $this->ok($this->eventManager->cancel($event), self::DETAIL_GROUPS);
    }

    #[Route('/events/{id}', name: 'api_events_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[IsGranted(EventVoter::DELETE, subject: 'event')]
    public function delete(Event $event): Response
    {
        $this->eventManager->delete($event);

        return $this->noContent();
    }
}
