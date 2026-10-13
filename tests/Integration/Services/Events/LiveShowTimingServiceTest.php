<?php

declare(strict_types=1);

use App\Services\Events\LiveShowTimingService;

test('wall times distinguish repeated hours and refuse skipped hours', function (?string $value, string $zone, ?string $offset, ?string $expected): void {
    // Act
    $instant = resolve(LiveShowTimingService::class)->wallTime($value, $zone, $offset);

    // Assert
    expect($instant?->format('Y-m-d H:i'))->toBe($expected);
})->with([
    'missing' => [null, 'UTC', null, null],
    'empty' => ['', 'UTC', null, null],
    'normal' => ['2026-10-13T19:30', 'America/New_York', null, '2026-10-13 23:30'],
    'spring gap' => ['2026-03-08T02:30', 'America/New_York', null, null],
    'repeated without offset' => ['2026-11-01T01:30', 'America/New_York', null, null],
    'first hour' => ['2026-11-01T01:30', 'America/New_York', '-04:00', '2026-11-01 05:30'],
    'second hour' => ['2026-11-01T01:30', 'America/New_York', '-05:00', '2026-11-01 06:30'],
    'wrong offset' => ['2026-11-01T01:30', 'America/New_York', '+01:00', null],
    'invalid calendar date' => ['2026-02-30T12:00', 'UTC', null, null],
]);
