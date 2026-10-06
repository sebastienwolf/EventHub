<?php

namespace App\Service;

use App\Dto\RegisterUserRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserRegistrar
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ValidationFailedException when the email is already used (UniqueEntity on User)
     */
    public function register(RegisterUserRequest $request, string $locale): User
    {
        $user = (new User())
            ->setEmail($request->email)
            ->setFirstName($request->firstName)
            ->setLastName($request->lastName)
            ->setLocale($locale);

        if (RegisterUserRequest::ACCOUNT_ORGANIZER === $request->accountType) {
            $user->addRole(User::ROLE_ORGANIZER);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));

        $violations = $this->validator->validate($user);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($user, $violations);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
