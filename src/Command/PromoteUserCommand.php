<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Laravel Artisan command equivalent.
 *
 * Usage: php bin/console app:user:promote ada@example.com admin
 */
#[AsCommand(name: 'app:user:promote', description: 'Grant the organizer or admin role to a user')]
final class PromoteUserCommand
{
    private const ROLES = [
        'organizer' => User::ROLE_ORGANIZER,
        'admin' => User::ROLE_ADMIN,
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Email of the user')] string $email,
        #[Argument(description: 'Role to grant: organizer or admin')] string $role = 'organizer',
    ): int {
        if (!isset(self::ROLES[$role])) {
            $io->error(\sprintf('Unknown role "%s", expected one of: %s.', $role, implode(', ', array_keys(self::ROLES))));

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneByEmail($email);
        if (null === $user) {
            $io->error(\sprintf('No user found with the email "%s".', $email));

            return Command::FAILURE;
        }

        $user->addRole(self::ROLES[$role]);
        $this->entityManager->flush();

        $io->success(\sprintf('%s is now %s. Roles: %s', $user->getFullName(), $role, implode(', ', $user->getRoles())));

        return Command::SUCCESS;
    }
}
