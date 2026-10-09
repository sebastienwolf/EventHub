<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testMinutesBetween(): void
    {
        $start = new \DateTimeImmutable('2026-12-01 09:00');

        self::assertSame(90, minutes_between($start, $start->modify('+90 minutes')));
        self::assertSame(0, minutes_between($start, $start->modify('-1 hour')), 'Never negative');
    }

    #[DataProvider('initialsProvider')]
    public function testStrInitials(string $name, string $expected): void
    {
        self::assertSame($expected, str_initials($name));
    }

    /** @return iterable<string, array{string, string}> */
    public static function initialsProvider(): iterable
    {
        yield 'first and last name' => ['Ada Lovelace', 'AL'];
        yield 'limited to two letters' => ['Jean Claude Van Damme', 'JC'];
        yield 'compound name' => ['jean-luc picard', 'JL'];
        yield 'accents' => ['Élodie Ündset', 'ÉÜ'];
        yield 'empty' => ['  ', ''];
    }
}
