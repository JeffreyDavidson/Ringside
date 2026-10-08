<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('PreviousStables query', function (): void {
    it('returns only ended stable memberships for the requested tag team in newest-first order', function (): void {
        $tagTeam = TagTeam::factory()->create();

        // Arrange
        $otherTagTeam = TagTeam::factory()->create();
        $recentStable = Stable::factory()->create();
        $olderStable = Stable::factory()->create();
        $currentStable = Stable::factory()->create();
        $otherStable = Stable::factory()->create();
        $olderStable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subMonths(4),
            'left_at' => Date::now()->subMonths(3),
        ]);
        $recentStable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subMonths(2),
            'left_at' => Date::now()->subMonth(),
        ]);
        $currentStable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subWeek(),
        ]);
        $otherStable->tagTeams()->attach($otherTagTeam, [
            'joined_at' => Date::now()->subDays(3),
            'left_at' => Date::now()->subDay(),
        ]);
        $table = new PreviousStables;
        $table->tagTeamId = $tagTeam->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables->modelKeys())->toBe([
            $recentStable->id,
            $olderStable->id,
        ]);
    });

    it('omits deleted stables', function (): void {
        $tagTeam = TagTeam::factory()->create();

        // Arrange
        $stable = Stable::factory()->create();
        $stable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subMonth(),
            'left_at' => Date::now()->subWeek(),
        ]);
        $stable->delete();
        $table = new PreviousStables;
        $table->tagTeamId = $tagTeam->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables)->toBeEmpty();
    });
});
