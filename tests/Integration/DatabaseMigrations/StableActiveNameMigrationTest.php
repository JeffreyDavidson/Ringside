<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;

test('the migration identifies existing duplicate active names', function () {
    dropActiveStableNameIndex();
    dropUnownedStableNameIndex();
    Stable::factory()->create(['name' => 'The Four Horsemen']);
    Stable::factory()->create(['name' => 'The Four Horsemen']);

    $migration = require database_path('migrations/2026_08_09_230854_enforce_unique_active_stable_names.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            'Cannot enforce unique active stable names. Resolve duplicate active names first: The Four Horsemen'
        );
});
