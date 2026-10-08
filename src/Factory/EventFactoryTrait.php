<?php

namespace App\Factory;

use App\Enum\EventStatus;

/**
 * Attributes and states shared by ConferenceFactory and WorkshopFactory,
 * mirroring the Event inheritance.
 */
trait EventFactoryTrait
{
    public function published(): self
    {
        return $this->with(['status' => EventStatus::Published]);
    }

    public function cancelled(): self
    {
        return $this->with(['status' => EventStatus::Cancelled]);
    }

    /**
     * @param string $start relative date understood by DateTimeImmutable, e.g. "+10 hours"
     */
    public function startingAt(string $start, string $duration = '+2 hours'): self
    {
        $startsAt = new \DateTimeImmutable($start);

        return $this->with(['startsAt' => $startsAt, 'endsAt' => $startsAt->modify($duration)]);
    }

    public function withCapacity(?int $capacity): self
    {
        return $this->with(['capacity' => $capacity]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventDefaults(): array
    {
        $startsAt = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('+3 days', '+4 months'))
            ->setTime(self::faker()->numberBetween(9, 18), 0);

        return [
            'title' => ucfirst(self::faker()->unique()->catchPhrase()),
            'description' => self::faker()->paragraphs(2, true),
            'startsAt' => $startsAt,
            'endsAt' => $startsAt->modify(\sprintf('+%d hours', self::faker()->numberBetween(1, 4))),
            'location' => self::faker()->city(),
            'capacity' => self::faker()->optional(0.7)->numberBetween(10, 200),
            'status' => EventStatus::Draft,
            'organizer' => UserFactory::new()->organizer(),
        ];
    }
}
