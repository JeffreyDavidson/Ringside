<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

describe('PreviousWrestlers query', function (): void {
    it('returns only ended memberships for the requested tag team in newest-first order', function (): void {
        // Arrange
        $otherTagTeam = TagTeam::factory()->create();
        $recentWrestler = Wrestler::factory()->create();
        $olderWrestler = Wrestler::factory()->create();
        $currentWrestler = Wrestler::factory()->create();
        $otherWrestler = Wrestler::factory()->create();

        TagTeamWrestler::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $olderWrestler->id,
            'joined_at' => Date::now()->subMonths(3),
            'left_at' => Date::now()->subMonths(2),
        ]);
        TagTeamWrestler::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $recentWrestler->id,
            'joined_at' => Date::now()->subMonth(),
            'left_at' => Date::now()->subWeek(),
        ]);
        TagTeamWrestler::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $currentWrestler->id,
            'joined_at' => Date::now()->subDays(3),
            'left_at' => null,
        ]);
        TagTeamWrestler::query()->create([
            'tag_team_id' => $otherTagTeam->id,
            'wrestler_id' => $otherWrestler->id,
            'joined_at' => Date::now()->subDays(2),
            'left_at' => Date::now()->subDay(),
        ]);
        $table = new PreviousWrestlers;
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $memberships = $table->builder()->get();

        // Assert
        expect($memberships->pluck('wrestler_id')->all())->toBe([
            $recentWrestler->id,
            $olderWrestler->id,
        ])->and($memberships->every->relationLoaded('wrestler'))->toBeTrue();
    });

    it('keeps separate historical memberships for a returning wrestler', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        TagTeamWrestler::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => Date::now()->subMonths(4),
            'left_at' => Date::now()->subMonths(3),
        ]);
        TagTeamWrestler::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => Date::now()->subMonths(2),
            'left_at' => Date::now()->subMonth(),
        ]);
        $table = new PreviousWrestlers;
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $memberships = $table->builder()->get();

        // Assert
        expect($memberships)->toHaveCount(2)
            ->and($memberships->pluck('wrestler_id')->all())->toBe([
                $wrestler->id,
                $wrestler->id,
            ]);
    });
});
