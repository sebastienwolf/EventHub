<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Laravel model Factory equivalent (Foundry).
 *
 * UserFactory::createOne(['email' => 'ada@example.com']);
 * UserFactory::new()->organizer()->many(3)->create();
 *
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public const DEFAULT_PASSWORD = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    public function organizer(): self
    {
        return $this->with(['roles' => [User::ROLE_ORGANIZER]]);
    }

    public function admin(): self
    {
        return $this->with(['roles' => [User::ROLE_ADMIN]]);
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'password' => self::DEFAULT_PASSWORD,
            'locale' => self::faker()->randomElement(User::SUPPORTED_LOCALES),
            'roles' => [],
        ];
    }

    protected function initialize(): static
    {
        // The "password" attribute is the plain password: hash it like the registration does
        return $this->afterInstantiate(function (User $user): void {
            $user->setPassword($this->passwordHasher->hashPassword($user, (string) $user->getPassword()));
        });
    }
}
