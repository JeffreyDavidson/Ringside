<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousTagTeams;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('PreviousTagTeams query', function (): void {
    it('returns only ended tag team memberships for the requested stable in newest-first order', function (): void {
        $stable = Stable::factory()->create();

        // Arrange
        $otherStable = Stable::factory()->create();
        $recentTagTeam = TagTeam::factory()->create();
        $olderTagTeam = TagTeam::factory()->create();
        $currentTagTeam = TagTeam::factory()->create();
        $otherTagTeam = TagTeam::factory()->create();

        StableTagTeam::query()->create([
            'stable_id' => $stable->id,
            'tag_team_id' => $olderTagTeam->id,
            'joined_at' => Date::now()->subMonths(4),
            'left_at' => Date::now()->subMonths(3),
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $stable->id,
            'tag_team_id' => $recentTagTeam->id,
            'joined_at' => Date::now()->subMonths(2),
            'left_at' => Date::now()->subMonth(),
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $stable->id,
            'tag_team_id' => $currentTagTeam->id,
            'joined_at' => Date::now()->subWeek(),
            'left_at' => null,
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $otherStable->id,
            'tag_team_id' => $otherTagTeam->id,
            'joined_at' => Date::now()->subDays(3),
            'left_at' => Date::now()->subDay(),
        ]);

        $table = new PreviousTagTeams;
        $table->stableId = $stable->id;

        // Act
        $memberships = $table->builder()->get();

        // Assert
        expect($memberships->pluck('tag_team_id')->all())->toBe([
            $recentTagTeam->id,
            $olderTagTeam->id,
        ])->and($memberships->every->relationLoaded('tagTeam'))->toBeTrue();
    });
});
