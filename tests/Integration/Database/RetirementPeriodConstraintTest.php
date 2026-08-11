<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\Managers\ManagerRetirement;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeRetirement;
use App\Models\Stables\Stable;
use App\Models\Stables\StableRetirement;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\Titles\Title;
use App\Models\Titles\TitleRetirement;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerRetirement;
use Illuminate\Database\QueryException;

test('a wrestler has only one open retirement period', function () {
    $wrestler = Wrestler::factory()->create();

    WrestlerRetirement::factory()->count(2)->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => now(),
    ]);
    WrestlerRetirement::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    expect(fn () => WrestlerRetirement::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a manager has only one open retirement period', function () {
    $manager = Manager::factory()->create();

    ManagerRetirement::factory()->count(2)->create([
        'manager_id' => $manager->id,
        'ended_at' => now(),
    ]);
    ManagerRetirement::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);

    expect(fn () => ManagerRetirement::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a referee has only one open retirement period', function () {
    $referee = Referee::factory()->create();

    RefereeRetirement::factory()->count(2)->create([
        'referee_id' => $referee->id,
        'ended_at' => now(),
    ]);
    RefereeRetirement::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);

    expect(fn () => RefereeRetirement::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a tag team has only one open retirement period', function () {
    $tagTeam = TagTeam::factory()->create();

    TagTeamRetirement::factory()->count(2)->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now(),
    ]);
    TagTeamRetirement::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => null,
    ]);

    expect(fn () => TagTeamRetirement::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a stable has only one open retirement period', function () {
    $stable = Stable::factory()->create();

    StableRetirement::factory()->count(2)->create([
        'stable_id' => $stable->id,
        'ended_at' => now(),
    ]);
    StableRetirement::factory()->create([
        'stable_id' => $stable->id,
        'ended_at' => null,
    ]);

    expect(fn () => StableRetirement::factory()->create([
        'stable_id' => $stable->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a title has only one open retirement period', function () {
    $title = Title::factory()->create();

    TitleRetirement::factory()->count(2)->create([
        'title_id' => $title->id,
        'ended_at' => now(),
    ]);
    TitleRetirement::factory()->create([
        'title_id' => $title->id,
        'ended_at' => null,
    ]);

    expect(fn () => TitleRetirement::factory()->create([
        'title_id' => $title->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});
