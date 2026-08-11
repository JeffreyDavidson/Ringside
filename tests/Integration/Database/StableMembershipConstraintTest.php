<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\QueryException;

test('a wrestler may have multiple ended stable memberships', function () {
    $wrestler = Wrestler::factory()->create();

    Stable::factory()->count(2)->create()->each(
        fn (Stable $stable) => StableWrestler::query()->create([
            'stable_id' => $stable->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => now()->subMonth(),
            'left_at' => now()->subDay(),
        ])
    );

    expect(StableWrestler::query()->where('wrestler_id', $wrestler->id)->count())->toBe(2);
});

test('a wrestler cannot have multiple current stable memberships', function () {
    $wrestler = Wrestler::factory()->create();
    $firstStable = Stable::factory()->create();
    $secondStable = Stable::factory()->create();

    StableWrestler::query()->create([
        'stable_id' => $firstStable->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => now(),
    ]);

    expect(fn () => StableWrestler::query()->create([
        'stable_id' => $secondStable->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('a tag team may have multiple ended stable memberships', function () {
    $tagTeam = TagTeam::factory()->create();

    Stable::factory()->count(2)->create()->each(
        fn (Stable $stable) => StableTagTeam::query()->create([
            'stable_id' => $stable->id,
            'tag_team_id' => $tagTeam->id,
            'joined_at' => now()->subMonth(),
            'left_at' => now()->subDay(),
        ])
    );

    expect(StableTagTeam::query()->where('tag_team_id', $tagTeam->id)->count())->toBe(2);
});

test('a tag team cannot have multiple current stable memberships', function () {
    $tagTeam = TagTeam::factory()->create();
    $firstStable = Stable::factory()->create();
    $secondStable = Stable::factory()->create();

    StableTagTeam::query()->create([
        'stable_id' => $firstStable->id,
        'tag_team_id' => $tagTeam->id,
        'joined_at' => now(),
    ]);

    expect(fn () => StableTagTeam::query()->create([
        'stable_id' => $secondStable->id,
        'tag_team_id' => $tagTeam->id,
        'joined_at' => now(),
    ]))->toThrow(QueryException::class);
});
