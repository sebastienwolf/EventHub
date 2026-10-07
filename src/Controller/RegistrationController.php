<?php

namespace App\Controller;

use App\Dto\PaginationQuery;
use App\Entity\Event;
use App\Repository\RegistrationRepository;
use App\Security\Voter\EventVoter;
use App\Service\RegistrationManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class RegistrationController extends AbstractApiController
{
    private const GROUPS = ['registration:read'];

    public function __construct(
        private readonly RegistrationManager $registrationManager,
        private readonly RegistrationRepository $registrationRepository,
    ) {
    }

    #[Route('/events/{id}/registrations', name: 'api_registrations_create', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    public function register(Event $event): JsonResponse
    {
        $registration = $this->registrationManager->register($event, $this->getAuthenticatedUser());

        return $this->created($registration, self::GROUPS);
    }

    #[Route('/events/{id}/registrations', name: 'api_registrations_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function unregister(Event $event): Response
    {
        $this->registrationManager->unregister($event, $this->getAuthenticatedUser());

        return $this->noContent();
    }

    /**
     * Participants list, reserved to the organizer of the event (or an admin).
     */
    #[Route('/events/{id}/registrations', name: 'api_registrations_list', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[IsGranted(EventVoter::EDIT, subject: 'event')]
    public function list(
        Event $event,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] PaginationQuery $pagination = new PaginationQuery(),
    ): JsonResponse {
        return $this->paginate($this->registrationRepository->createByEventQueryBuilder($event), $pagination, self::GROUPS);
    }

    #[Route('/me/registrations', name: 'api_my_registrations', methods: ['GET'])]
    public function mine(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] PaginationQuery $pagination = new PaginationQuery(),
    ): JsonResponse {
        $queryBuilder = $this->registrationRepository->createByParticipantQueryBuilder($this->getAuthenticatedUser());

        return $this->paginate($queryBuilder, $pagination, self::GROUPS);
    }
}
