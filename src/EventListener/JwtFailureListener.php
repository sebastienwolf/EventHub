<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Rewrites LexikJWT's authentication errors to the API error format,
 * translated into the request locale.
 */
final class JwtFailureListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsEventListener(event: Events::AUTHENTICATION_FAILURE)]
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $this->respond($event, 'error.auth.invalid_credentials');
    }

    #[AsEventListener(event: Events::JWT_NOT_FOUND)]
    public function onJwtNotFound(AuthenticationFailureEvent $event): void
    {
        $this->respond($event, 'error.auth.token_missing');
    }

    #[AsEventListener(event: Events::JWT_INVALID)]
    public function onJwtInvalid(AuthenticationFailureEvent $event): void
    {
        $this->respond($event, 'error.auth.token_invalid');
    }

    #[AsEventListener(event: Events::JWT_EXPIRED)]
    public function onJwtExpired(AuthenticationFailureEvent $event): void
    {
        $this->respond($event, 'error.auth.token_expired');
    }

    private function respond(AuthenticationFailureEvent $event, string $translationKey): void
    {
        $status = $event->getResponse()?->getStatusCode() ?? Response::HTTP_UNAUTHORIZED;

        // Keeps the "WWW-Authenticate: Bearer" header set by Lexik
        $headers = $event->getResponse()?->headers->all() ?? [];
        unset($headers['content-type'], $headers['content-length']);

        $response = new JsonResponse([
            'error' => [
                'code' => $status,
                'message' => $this->translator->trans($translationKey),
            ],
        ], $status, $headers);

        $event->setResponse($response->setEncodingOptions($response->getEncodingOptions() | \JSON_UNESCAPED_UNICODE));
    }
}
