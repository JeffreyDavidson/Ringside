<?php

declare(strict_types=1);

use App\Models\Concerns\HasMembers;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Unit tests for HasMembers trait functionality.
 *
 * UNIT TEST SCOPE:
 * - Trait relationship methods and their return types
 * - Wrestler membership functionality
 * - Tag team membership functionality
 * - Current and previous member filtering
 * - Trait integration with stable models
 *
 * These tests verify that the HasMembers trait correctly
 * provides member relationship functionality in isolation.
 */
describe('HasMembers Trait Unit Tests', function () {
    describe('wrestler relationship methods', function () {
        test('wrestlers relationship returns correct type', function () {
            $stable = Stable::factory()->make();
            expect($stable->wrestlers())->toBeInstanceOf(BelongsToMany::class);
        });

        test('currentWrestlers relationship returns correct type', function () {
            $stable = Stable::factory()->make();
            expect($stable->currentWrestlers())->toBeInstanceOf(BelongsToMany::class);
        });

        test('previousWrestlers relationship returns correct type', function () {
            $stable = Stable::factory()->make();
            expect($stable->previousWrestlers())->toBeInstanceOf(BelongsToMany::class);
        });
    });

    describe('wrestler membership functionality', function () {
        test('stable can have wrestlers', function () {
            $stable = Stable::factory()->create();
            $wrestler = Wrestler::factory()->create();

            $stable->wrestlers()->attach($wrestler->id, ['joined_at' => now()]);

            expect($stable->wrestlers->pluck('id'))->toContain($wrestler->id);
        });

        test('stable can have no wrestlers', function () {
            $stable = Stable::factory()->create();
            expect($stable->wrestlers)->toBeEmpty();
        });

        test('stable can check if it has wrestlers', function () {
            $stable = Stable::factory()->create();
            $wrestler = Wrestler::factory()->create();

            $stable->wrestlers()->attach($wrestler->id, ['joined_at' => now()]);

            expect($stable->wrestlers->count())->toBe(1);
        });

        test('currentWrestlers returns only active members', function () {
            $stable = Stable::factory()->create();
            $activeWrestler = Wrestler::factory()->create();
            $formerWrestler = Wrestler::factory()->create();

            // Add active wrestler
            $stable->wrestlers()->attach($activeWrestler->id, ['joined_at' => now()]);

            // Add former wrestler (with left_at date)
            $stable->wrestlers()->attach($formerWrestler->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($stable->currentWrestlers->pluck('id'))->toContain($activeWrestler->id);
            expect($stable->currentWrestlers->pluck('id'))->not->toContain($formerWrestler->id);
        });

        test('previousWrestlers returns only former members', function () {
            $stable = Stable::factory()->create();
            $activeWrestler = Wrestler::factory()->create();
            $formerWrestler = Wrestler::factory()->create();

            // Add active wrestler
            $stable->wrestlers()->attach($activeWrestler->id, ['joined_at' => now()]);

            // Add former wrestler (with left_at date)
            $stable->wrestlers()->attach($formerWrestler->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($stable->previousWrestlers->pluck('id'))->toContain($formerWrestler->id);
            expect($stable->previousWrestlers->pluck('id'))->not->toContain($activeWrestler->id);
        });
    });

    describe('tag team relationship methods', function () {
        test('tagTeams relationship returns correct type', function () {
            $stable = Stable::factory()->make();
            expect($stable->tagTeams())->toBeInstanceOf(BelongsToMany::class);
        });

        test('currentTagTeams relationship returns correct type', function () {
            $stable = Stable::factory()->make();
            expect($stable->currentTagTeams())->toBeInstanceOf(BelongsToMany::class);
        });

        test('previousTagTeams relationship returns correct type', function () {
            $stable = Stable::factory()->make();
            expect($stable->previousTagTeams())->toBeInstanceOf(BelongsToMany::class);
        });
    });

    describe('tag team membership functionality', function () {
        test('stable can have tag teams', function () {
            $stable = Stable::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            $stable->tagTeams()->attach($tagTeam->id, ['joined_at' => now()]);

            expect($stable->tagTeams->pluck('id'))->toContain($tagTeam->id);
        });

        test('stable can have no tag teams', function () {
            $stable = Stable::factory()->create();
            expect($stable->tagTeams)->toBeEmpty();
        });

        test('currentTagTeams returns only active members', function () {
            $stable = Stable::factory()->create();
            $activeTagTeam = TagTeam::factory()->create();
            $formerTagTeam = TagTeam::factory()->create();

            // Add active tag team
            $stable->tagTeams()->attach($activeTagTeam->id, ['joined_at' => now()]);

            // Add former tag team (with left_at date)
            $stable->tagTeams()->attach($formerTagTeam->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($stable->currentTagTeams->pluck('id'))->toContain($activeTagTeam->id);
            expect($stable->currentTagTeams->pluck('id'))->not->toContain($formerTagTeam->id);
        });

        test('previousTagTeams returns only former members', function () {
            $stable = Stable::factory()->create();
            $activeTagTeam = TagTeam::factory()->create();
            $formerTagTeam = TagTeam::factory()->create();

            // Add active tag team
            $stable->tagTeams()->attach($activeTagTeam->id, ['joined_at' => now()]);

            // Add former tag team (with left_at date)
            $stable->tagTeams()->attach($formerTagTeam->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($stable->previousTagTeams->pluck('id'))->toContain($formerTagTeam->id);
            expect($stable->previousTagTeams->pluck('id'))->not->toContain($activeTagTeam->id);
        });
    });

    describe('trait integration', function () {
        test('Stable model uses HasMembers trait', function () {
            expect(Stable::class)->usesTrait(HasMembers::class);
        });
    });
});
