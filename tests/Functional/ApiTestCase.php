<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Base class of the API tests: each test runs in a transaction rolled back
 * afterwards (DAMA), with Foundry factories to build the data.
 */
abstract class ApiTestCase extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Sends a JSON request, authenticated with a real JWT when a user is given.
     *
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed> decoded JSON response (empty for 204)
     */
    protected function request(string $method, string $uri, ?array $body = null, ?User $as = null, string $locale = 'en'): array
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $locale,
        ];

        if (null !== $as) {
            $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($as);
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request($method, $uri, server: $server, content: null !== $body ? json_encode($body, \JSON_THROW_ON_ERROR) : null);

        $content = (string) $this->client->getResponse()->getContent();

        return '' !== $content ? json_decode($content, true, flags: \JSON_THROW_ON_ERROR) : [];
    }

    protected function asyncTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');

        return $transport;
    }

    /**
     * @return list<object> messages sent to the async transport, unwrapped from their envelopes
     */
    protected function queuedMessages(?string $class = null): array
    {
        $messages = array_map(static fn ($envelope): object => $envelope->getMessage(), $this->asyncTransport()->getSent());

        return array_values(null === $class ? $messages : array_filter($messages, static fn (object $m): bool => $m instanceof $class));
    }
}
