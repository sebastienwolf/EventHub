<?php

namespace App\Factory;

use App\Entity\Workshop;
use App\Enum\WorkshopLevel;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Workshop>
 */
final class WorkshopFactory extends PersistentObjectFactory
{
    use EventFactoryTrait;

    public static function class(): string
    {
        return Workshop::class;
    }

    protected function defaults(): array
    {
        return [
            ...$this->eventDefaults(),
            'capacity' => self::faker()->numberBetween(8, 30),
            'level' => self::faker()->randomElement(WorkshopLevel::cases()),
            'prerequisites' => self::faker()->optional()->sentence(),
        ];
    }
}
