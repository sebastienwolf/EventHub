<?php

namespace App\EventListener;

use App\Exception\BusinessRuleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Laravel "Exception Handler" equivalent: turns every exception thrown under /api
 * into a JSON error with a translated message.
 *
 * {"error": {"code": 422, "message": "...", "violations": [{"property": "email", "message": "..."}]}}
 *
 * Runs after the Security ExceptionListener (priority 1), which converts
 * AccessDeniedException into a 403 HTTP exception.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final class ApiExceptionListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $error = match (true) {
            $exception instanceof BusinessRuleException => [
                'code' => $exception->getStatusCode(),
                'message' => $this->translator->trans($exception->getTranslationKey(), $exception->getParameters()),
            ],
            $exception instanceof ValidationFailedException => $this->fromValidationFailure($exception),
            $exception instanceof HttpExceptionInterface => $this->fromHttpException($exception),
            default => [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->translator->trans('error.http.500'),
            ],
        };

        if (Response::HTTP_INTERNAL_SERVER_ERROR === $error['code']) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            if ($this->debug) {
                $error['debug'] = ['class' => $exception::class, 'message' => $exception->getMessage()];
            }
        }

        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
        $event->setResponse(new JsonResponse(['error' => $error], $error['code'], $headers));
    }

    /**
     * @return array{code: int, message: string, violations?: list<array{property: string, message: string}>}
     */
    private function fromHttpException(HttpExceptionInterface $exception): array
    {
        // #[MapRequestPayload] / #[MapQueryString] wrap validation errors in a 422 HttpException
        $previous = $exception instanceof \Throwable ? $exception->getPrevious() : null;
        if ($previous instanceof ValidationFailedException) {
            return $this->fromValidationFailure($previous, $exception->getStatusCode());
        }

        $code = $exception->getStatusCode();

        return ['code' => $code, 'message' => $this->translateStatus($code)];
    }

    /**
     * @return array{code: int, message: string, violations: list<array{property: string, message: string}>}
     */
    private function fromValidationFailure(ValidationFailedException $exception, int $code = Response::HTTP_UNPROCESSABLE_ENTITY): array
    {
        return [
            'code' => $code,
            'message' => $this->translateStatus($code),
            'violations' => array_map(
                static fn (ConstraintViolationInterface $violation): array => [
                    'property' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ],
                iterator_to_array($exception->getViolations(), false),
            ),
        ];
    }

    private function translateStatus(int $code): string
    {
        $key = 'error.http.'.$code;
        $message = $this->translator->trans($key);

        return $message !== $key ? $message : (Response::$statusTexts[$code] ?? 'Error');
    }
}
