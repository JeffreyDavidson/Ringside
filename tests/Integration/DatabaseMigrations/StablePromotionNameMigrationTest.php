<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;

test('the migration lists the stables that duplicate a name within a promotion before changing anything', function () {
    $promotion = Promotion::factory()->create();
    dropEnforcingIndex('stables', 'stables_active_name_unique');
    dropUnownedStableNameIndex();
    $first = Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Four Horsemen']);
    $second = Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Four Horsemen']);
    Stable::factory()->create(['name' => 'Unowned']);
    Stable::factory()->create(['name' => 'Unowned']);
    $migration = require database_path('migrations/2026_10_01_190000_scope_unique_active_stable_names_to_promotion.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "\"The Four Horsemen\" in promotion {$promotion->id} (stable ids {$first->id}, {$second->id})",
        );
});
