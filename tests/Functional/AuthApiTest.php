<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;

final class AuthApiTest extends ApiTestCase
{
    public function testRegisterCreatesAParticipantInTheRequestLocale(): void
    {
        $data = $this->request('POST', '/api/auth/register', [
            'email' => 'Ada@Example.com',
            'password' => 'secret123',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
        ], locale: 'fr');

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('Content-Language', 'fr');
        self::assertSame('ada@example.com', $data['email']);
        self::assertSame(['ROLE_PARTICIPANT'], $data['roles']);
        self::assertSame('AL', $data['initials']);
        self::assertArrayNotHasKey('password', $data);

        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail('ada@example.com');
        self::assertSame('fr', $user?->getLocale());
    }

    public function testRegisterAsOrganizer(): void
    {
        $data = $this->request('POST', '/api/auth/register', [
            'email' => 'grace@example.com',
            'password' => 'secret123',
            'firstName' => 'Grace',
            'lastName' => 'Hopper',
            'accountType' => 'organizer',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertContains(User::ROLE_ORGANIZER, $data['roles']);
    }

    public function testRegisterRejectsInvalidDataWithTranslatedViolations(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);

        $data = $this->request('POST', '/api/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'short',
            'firstName' => '',
            'lastName' => 'Doe',
            'accountType' => 'admin',
        ], locale: 'fr');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Les données envoyées sont invalides.', $data['error']['message']);
        self::assertEqualsCanonicalizing(
            ['password', 'firstName', 'accountType'],
            array_column($data['error']['violations'], 'property'),
        );
    }

    public function testRegisterRejectsAnEmailAlreadyUsed(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);

        $data = $this->request('POST', '/api/auth/register', [
            'email' => 'TAKEN@example.com',
            'password' => 'secret123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('email', $data['error']['violations'][0]['property']);
        self::assertSame('This email address is already used.', $data['error']['violations'][0]['message']);
    }

    public function testLoginReturnsAJwtUsableOnProtectedRoutes(): void
    {
        UserFactory::createOne(['email' => 'ada@example.com', 'password' => 'secret123']);

        $data = $this->request('POST', '/api/auth/login', ['email' => 'ada@example.com', 'password' => 'secret123']);

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $data);

        $this->client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$data['token']]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('ada@example.com', (string) $this->client->getResponse()->getContent());
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        UserFactory::createOne(['email' => 'ada@example.com', 'password' => 'secret123']);

        $data = $this->request('POST', '/api/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong'], locale: 'fr');

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Adresse e-mail ou mot de passe incorrect.', $data['error']['message']);
    }

    public function testProtectedRouteRequiresAToken(): void
    {
        $data = $this->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Authentication token not found.', $data['error']['message']);
    }
}
