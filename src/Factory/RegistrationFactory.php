<?php

namespace App\Factory;

use App\Entity\Registration;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * "event" and "participant" are passed to the Registration constructor.
 *
 * @extends PersistentObjectFactory<Registration>
 */
final class RegistrationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Registration::class;
    }

    protected function defaults(): array
    {
        return [
            'event' => ConferenceFactory::new()->published(),
            'participant' => UserFactory::new(),
        ];
    }
}
