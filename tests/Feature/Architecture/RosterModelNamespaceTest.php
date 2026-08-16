<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('roster models use the roster namespace', function () {
    expect([
        Manager::class,
        Referee::class,
        Stable::class,
        TagTeam::class,
        Wrestler::class,
    ])->each->toStartWith('App\\Models\\Roster\\');
});

test('legacy top-level roster model and factory directories do not return', function () {
    $entityDirectories = ['Managers', 'Referees', 'Stables', 'TagTeams', 'Wrestlers'];

    $legacyDirectories = collect($entityDirectories)
        ->flatMap(fn (string $directory): array => [
            app_path("Models/{$directory}"),
            database_path("factories/{$directory}"),
        ])
        ->filter(fn (string $directory): bool => is_dir($directory));

    expect($legacyDirectories)->toBeEmpty();
});

test('immutable migrations can resolve legacy roster model class names', function (string $legacyClass, string $rosterClass) {
    expect(class_exists($legacyClass))->toBeTrue()
        ->and(is_a($legacyClass, $rosterClass, true))->toBeTrue();
})->with([
    'manager' => ['App\\Models\\Managers\\Manager', Manager::class],
    'referee' => ['App\\Models\\Referees\\Referee', Referee::class],
    'stable' => ['App\\Models\\Stables\\Stable', Stable::class],
    'tag team' => ['App\\Models\\TagTeams\\TagTeam', TagTeam::class],
    'wrestler' => ['App\\Models\\Wrestlers\\Wrestler', Wrestler::class],
]);
