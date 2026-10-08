<?php

namespace App\Factory;

use App\Entity\Conference;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Conference>
 */
final class ConferenceFactory extends PersistentObjectFactory
{
    use EventFactoryTrait;

    public static function class(): string
    {
        return Conference::class;
    }

    protected function defaults(): array
    {
        return [
            ...$this->eventDefaults(),
            'speakers' => array_map(
                static fn (): string => self::faker()->name(),
                range(1, self::faker()->numberBetween(1, 3)),
            ),
        ];
    }
}
