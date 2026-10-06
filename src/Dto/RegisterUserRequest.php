<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Laravel FormRequest equivalent: the JSON body is deserialized into this object
 * by #[MapRequestPayload], then validated. Invalid data returns a 422 response.
 */
final class RegisterUserRequest
{
    public const ACCOUNT_PARTICIPANT = 'participant';
    public const ACCOUNT_ORGANIZER = 'organizer';

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public readonly string $password = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public readonly string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public readonly string $lastName = '',

        /** Admins can only be created with the app:user:promote command. */
        #[Assert\Choice(choices: [self::ACCOUNT_PARTICIPANT, self::ACCOUNT_ORGANIZER])]
        public readonly string $accountType = self::ACCOUNT_PARTICIPANT,
    ) {
    }
}
