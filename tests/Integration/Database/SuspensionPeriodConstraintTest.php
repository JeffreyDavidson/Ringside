<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\Managers\ManagerSuspension;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeSuspension;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamSuspension;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerSuspension;
use Illuminate\Database\QueryException;

test('a wrestler has only one open suspension period', function () {
    $wrestler = Wrestler::factory()->create();

    WrestlerSuspension::factory()->count(2)->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => now(),
    ]);
    WrestlerSuspension::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    expect(fn () => WrestlerSuspension::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a manager has only one open suspension period', function () {
    $manager = Manager::factory()->create();

    ManagerSuspension::factory()->count(2)->create([
        'manager_id' => $manager->id,
        'ended_at' => now(),
    ]);
    ManagerSuspension::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);

    expect(fn () => ManagerSuspension::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a referee has only one open suspension period', function () {
    $referee = Referee::factory()->create();

    RefereeSuspension::factory()->count(2)->create([
        'referee_id' => $referee->id,
        'ended_at' => now(),
    ]);
    RefereeSuspension::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);

    expect(fn () => RefereeSuspension::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a tag team has only one open suspension period', function () {
    $tagTeam = TagTeam::factory()->create();

    TagTeamSuspension::factory()->count(2)->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now(),
    ]);
    TagTeamSuspension::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => null,
    ]);

    expect(fn () => TagTeamSuspension::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});
