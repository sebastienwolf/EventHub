<?php

namespace App\Controller;

use App\Dto\RegisterUserRequest;
use App\Service\UserRegistrar;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AuthController extends AbstractApiController
{
    #[Route('/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterUserRequest $payload,
        Request $request,
        UserRegistrar $registrar,
        RateLimiterFactoryInterface $registrationLimiter,
    ): JsonResponse {
        $limit = $registrationLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
        }

        $user = $registrar->register($payload, $request->getLocale());

        return $this->created($user, ['user:read'], $this->generateUrl('api_me'));
    }

    /**
     * Handled by the "json_login" authenticator of the "login" firewall:
     * this method is never executed, the route only has to exist.
     */
    #[Route('/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('This code should never be reached: the login firewall intercepts the request.');
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return $this->ok($this->getAuthenticatedUser(), ['user:read']);
    }
}
