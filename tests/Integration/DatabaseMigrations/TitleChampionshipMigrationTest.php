<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('the migration lists the titles with several open reigns before changing anything', function () {
    // Arrange
    $title = Title::factory()->create();
    dropEnforcingIndex('titles_championships', 'titles_championships_one_open_reign_unique', 'open_reign_title_id');
    [$first, $second] = TitleChampionship::factory()->for($title)->count(2)->create(['lost_at' => null])->all();
    $migration = require database_path('migrations/2026_10_05_024420_enforce_single_open_reign_per_title.php');
    $up = new ReflectionMethod($migration, 'up');

    // Act
    $runMigration = fn () => $up->invoke($migration);

    // Assert
    expect($runMigration)
        ->toThrow(
            RuntimeException::class,
            "title {$title->id} has open reign ids {$first->id}, {$second->id}",
        );
});
