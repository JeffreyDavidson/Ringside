<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;

test('the reign migration lists the titles with inverted reigns, deleted ones included, before changing anything', function () {
    // Arrange
    dropDateOrderConstraints();
    $firstTitle = Title::factory()->create();
    $secondTitle = Title::factory()->create();
    $champion = ['champion_type' => 'wrestler', 'champion_id' => Wrestler::factory()->create()->id];
    $firstColumns = ['title_id' => $firstTitle->id, ...$champion];
    $secondColumns = ['title_id' => $secondTitle->id, ...$champion];
    $first = DB::table('titles_championships')->insertGetId([...$firstColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-09 12:00:00']);
    DB::table('titles_championships')->insert([...$firstColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-10 12:00:00']);
    $second = DB::table('titles_championships')->insertGetId([...$firstColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-08 12:00:00', 'deleted_at' => '2026-03-11 12:00:00']);
    $third = DB::table('titles_championships')->insertGetId([...$secondColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-01 12:00:00']);
    $migration = require database_path('migrations/2026_10_05_160946_enforce_date_order_for_title_reigns.php');
    $up = new ReflectionMethod($migration, 'up');

    // Act
    $runMigration = fn () => $up->invoke($migration);

    // Assert
    expect($runMigration)
        ->toThrow(
            RuntimeException::class,
            "title {$firstTitle->id} has reign ids {$first}, {$second}; title {$secondTitle->id} has reign ids {$third}. No changes were made.",
        );

    $invertedReign = fn (): bool => DB::table('titles_championships')->insert([...periodRowColumns('titles_championships'), 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-09 12:00:00']);
    expect($invertedReign())->toBeTrue();
});

test('the migration lists every row that ends before it starts before changing anything', function () {
    // Arrange
    dropDateOrderConstraints();
    $firstEmployment = DB::table('employments')->insertGetId([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-09 12:00:00']);
    DB::table('employments')->insert([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-10 12:00:00']);
    $secondEmployment = DB::table('employments')->insertGetId([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-08 12:00:00']);
    $managerPeriod = DB::table('wrestlers_managers')->insertGetId([...periodRowColumns('wrestlers_managers'), 'hired_at' => '2026-03-10 12:00:00', 'fired_at' => '2026-03-01 12:00:00']);
    $migration = require database_path('migrations/2026_10_05_154301_enforce_date_order_for_period_tables.php');
    $up = new ReflectionMethod($migration, 'up');

    // Act
    $runMigration = fn () => $up->invoke($migration);

    // Assert
    expect($runMigration)
        ->toThrow(
            RuntimeException::class,
            "employments ids {$firstEmployment}, {$secondEmployment}; wrestlers_managers ids {$managerPeriod}. No changes were made.",
        );

    $invertedInjury = fn (): bool => DB::table('injuries')->insert([...periodRowColumns('injuries'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-09 12:00:00']);
    expect($invertedInjury())->toBeTrue();
});
