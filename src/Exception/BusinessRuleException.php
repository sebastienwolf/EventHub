<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for domain rule violations (event full, already registered...).
 *
 * The message is a translation key: App\EventListener\ApiExceptionListener
 * translates it into the request locale.
 */
abstract class BusinessRuleException extends \DomainException
{
    /**
     * @param array<string, string|int> $parameters translation parameters
     */
    public function __construct(
        string $translationKey,
        private readonly array $parameters = [],
        private readonly int $statusCode = Response::HTTP_CONFLICT,
    ) {
        parent::__construct($translationKey);
    }

    public function getTranslationKey(): string
    {
        return $this->getMessage();
    }

    /** @return array<string, string|int> */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
