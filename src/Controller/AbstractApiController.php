<?php

namespace App\Controller;

use App\Dto\PaginationQuery;
use App\Entity\User;
use App\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parent of every API controller: consistent JSON responses and pagination.
 *
 * Extra services are exposed through the controller's service locator
 * (getSubscribedServices) instead of the constructor, so child controllers
 * keep their own constructor free.
 */
abstract class AbstractApiController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            Paginator::class => Paginator::class,
        ];
    }

    /**
     * @param list<string> $groups serialization groups
     */
    protected function ok(mixed $data, array $groups = [], int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        $context = [] !== $groups ? ['groups' => $groups] : [];

        return $this->json($data, $status, $headers, $context);
    }

    /**
     * @param list<string> $groups
     */
    protected function created(mixed $data, array $groups = [], ?string $location = null): JsonResponse
    {
        return $this->ok($data, $groups, Response::HTTP_CREATED, null !== $location ? ['Location' => $location] : []);
    }

    protected function noContent(): Response
    {
        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Returns {"data": [...], "meta": {"page", "limit", "total", "pages"}}.
     *
     * @param list<string> $groups
     */
    protected function paginate(QueryBuilder $queryBuilder, PaginationQuery $pagination, array $groups = []): JsonResponse
    {
        $result = $this->container->get(Paginator::class)->paginate($queryBuilder, $pagination->page, $pagination->limit);

        return $this->ok(['data' => $result->items, 'meta' => $result->getMeta()], $groups);
    }

    /**
     * Typed shortcut for routes protected by the firewall.
     */
    protected function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
