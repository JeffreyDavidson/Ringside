<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\Managers\ManagerEmployment;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeEmployment;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamEmployment;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use Illuminate\Database\QueryException;

test('a wrestler has only one open employment period', function () {
    $wrestler = Wrestler::factory()->create();

    WrestlerEmployment::factory()->count(2)->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => now(),
    ]);
    WrestlerEmployment::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    expect(fn () => WrestlerEmployment::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a manager has only one open employment period', function () {
    $manager = Manager::factory()->create();

    ManagerEmployment::factory()->count(2)->create([
        'manager_id' => $manager->id,
        'ended_at' => now(),
    ]);
    ManagerEmployment::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);

    expect(fn () => ManagerEmployment::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a referee has only one open employment period', function () {
    $referee = Referee::factory()->create();

    RefereeEmployment::factory()->count(2)->create([
        'referee_id' => $referee->id,
        'ended_at' => now(),
    ]);
    RefereeEmployment::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);

    expect(fn () => RefereeEmployment::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a tag team has only one open employment period', function () {
    $tagTeam = TagTeam::factory()->create();

    TagTeamEmployment::factory()->count(2)->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now(),
    ]);
    TagTeamEmployment::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => null,
    ]);

    expect(fn () => TagTeamEmployment::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});
