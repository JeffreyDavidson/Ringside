<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousTagTeams;
use App\Livewire\Stables\Tables\PreviousWrestlers;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());

    $this->stable = Stable::factory()->create();
});

test('previous wrestlers renders typed membership history for one stable', function () {
    $formerWrestler = Wrestler::factory()->create();
    $currentWrestler = Wrestler::factory()->create();
    $otherStableWrestler = Wrestler::factory()->create();
    $otherStable = Stable::factory()->create();
    $joinedAt = now()->subMonths(3);
    $leftAt = now()->subMonth();

    StableWrestler::query()->create([
        'stable_id' => $this->stable->id,
        'wrestler_id' => $formerWrestler->id,
        'joined_at' => $joinedAt,
        'left_at' => $leftAt,
    ]);
    StableWrestler::query()->create([
        'stable_id' => $this->stable->id,
        'wrestler_id' => $currentWrestler->id,
        'joined_at' => now()->subWeek(),
    ]);
    StableWrestler::query()->create([
        'stable_id' => $otherStable->id,
        'wrestler_id' => $otherStableWrestler->id,
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subWeek(),
    ]);

    livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id])
        ->assertOk()
        ->assertSee($formerWrestler->name)
        ->assertSee(route('wrestlers.show', $formerWrestler))
        ->assertSee($joinedAt->format('Y-m-d'))
        ->assertSee($leftAt->format('Y-m-d'))
        ->assertDontSee($currentWrestler->name)
        ->assertDontSee($otherStableWrestler->name);
});

test('previous tag teams renders typed membership history for one stable', function () {
    $formerTagTeam = TagTeam::factory()->create();
    $currentTagTeam = TagTeam::factory()->create();
    $otherStableTagTeam = TagTeam::factory()->create();
    $otherStable = Stable::factory()->create();
    $joinedAt = now()->subMonths(4);
    $leftAt = now()->subMonths(2);

    StableTagTeam::query()->create([
        'stable_id' => $this->stable->id,
        'tag_team_id' => $formerTagTeam->id,
        'joined_at' => $joinedAt,
        'left_at' => $leftAt,
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $this->stable->id,
        'tag_team_id' => $currentTagTeam->id,
        'joined_at' => now()->subWeek(),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $otherStable->id,
        'tag_team_id' => $otherStableTagTeam->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonth(),
    ]);

    livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id])
        ->assertOk()
        ->assertSee($formerTagTeam->name)
        ->assertSee(route('tag-teams.show', $formerTagTeam))
        ->assertSee($joinedAt->format('Y-m-d'))
        ->assertSee($leftAt->format('Y-m-d'))
        ->assertDontSee($currentTagTeam->name)
        ->assertDontSee($otherStableTagTeam->name);
});
