<?php

declare(strict_types=1);

use App\Models\Concerns\ProvidesTagTeamWrestlers;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Unit tests for ProvidesTagTeamWrestlers trait functionality.
 *
 * UNIT TEST SCOPE:
 * - Trait relationship methods and their return types
 * - Wrestler membership functionality
 * - Current and previous wrestler filtering
 * - Combined weight calculations
 * - Trait integration with tag team models
 *
 * These tests verify that the ProvidesTagTeamWrestlers trait correctly
 * provides wrestler relationship functionality in isolation.
 */
describe('ProvidesTagTeamWrestlers Trait Unit Tests', function () {
    describe('wrestler relationship methods', function () {
        test('wrestlers relationship returns correct type', function () {
            $tagTeam = TagTeam::factory()->make();
            expect($tagTeam->wrestlers())->toBeInstanceOf(BelongsToMany::class);
        });

        test('currentWrestlers relationship returns correct type', function () {
            $tagTeam = TagTeam::factory()->make();
            expect($tagTeam->currentWrestlers())->toBeInstanceOf(BelongsToMany::class);
        });

        test('previousWrestlers relationship returns correct type', function () {
            $tagTeam = TagTeam::factory()->make();
            expect($tagTeam->previousWrestlers())->toBeInstanceOf(BelongsToMany::class);
        });
    });

    describe('wrestler membership functionality', function () {
        test('tag team can have wrestlers', function () {
            $tagTeam = TagTeam::factory()->create();
            $wrestler = Wrestler::factory()->create();

            $tagTeam->wrestlers()->attach($wrestler->id, ['joined_at' => now()]);

            expect($tagTeam->wrestlers->pluck('id'))->toContain($wrestler->id);
        });

        test('tag team can have no wrestlers', function () {
            $tagTeam = TagTeam::factory()->create();
            expect($tagTeam->wrestlers)->toBeEmpty();
        });

        test('currentWrestlers returns only active members', function () {
            $tagTeam = TagTeam::factory()->create();
            $activeWrestler = Wrestler::factory()->create();
            $formerWrestler = Wrestler::factory()->create();

            // Add active wrestler
            $tagTeam->wrestlers()->attach($activeWrestler->id, ['joined_at' => now()]);

            // Add former wrestler (with left_at date)
            $tagTeam->wrestlers()->attach($formerWrestler->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($tagTeam->currentWrestlers->pluck('id'))->toContain($activeWrestler->id);
            expect($tagTeam->currentWrestlers->pluck('id'))->not->toContain($formerWrestler->id);
        });

        test('previousWrestlers returns only former members', function () {
            $tagTeam = TagTeam::factory()->create();
            $activeWrestler = Wrestler::factory()->create();
            $formerWrestler = Wrestler::factory()->create();

            // Add active wrestler
            $tagTeam->wrestlers()->attach($activeWrestler->id, ['joined_at' => now()]);

            // Add former wrestler (with left_at date)
            $tagTeam->wrestlers()->attach($formerWrestler->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($tagTeam->previousWrestlers->pluck('id'))->toContain($formerWrestler->id);
            expect($tagTeam->previousWrestlers->pluck('id'))->not->toContain($activeWrestler->id);
        });
    });

    describe('combined weight calculations', function () {
        test('combinedWeight returns sum of current wrestler weights', function () {
            $tagTeam = TagTeam::factory()->create();
            $wrestler1 = Wrestler::factory()->create(['weight' => 200]);
            $wrestler2 = Wrestler::factory()->create(['weight' => 180]);

            $tagTeam->wrestlers()->attach($wrestler1->id, ['joined_at' => now()]);
            $tagTeam->wrestlers()->attach($wrestler2->id, ['joined_at' => now()]);

            expect($tagTeam->combined_weight)->toBe(380);
        });

        test('combinedWeight excludes former wrestlers', function () {
            $tagTeam = TagTeam::factory()->create();
            $activeWrestler = Wrestler::factory()->create(['weight' => 200]);
            $formerWrestler = Wrestler::factory()->create(['weight' => 180]);

            // Add active wrestler
            $tagTeam->wrestlers()->attach($activeWrestler->id, ['joined_at' => now()]);

            // Add former wrestler
            $tagTeam->wrestlers()->attach($formerWrestler->id, [
                'joined_at' => now()->subDays(10),
                'left_at' => now()->subDays(1),
            ]);

            expect($tagTeam->combined_weight)->toBe(200);
        });

        test('combinedWeight returns zero when no current wrestlers', function () {
            $tagTeam = TagTeam::factory()->create();
            expect($tagTeam->combined_weight)->toBe(0);
        });
    });

    describe('trait integration', function () {
        test('TagTeam model uses ProvidesTagTeamWrestlers trait', function () {
            expect(TagTeam::class)->usesTrait(ProvidesTagTeamWrestlers::class);
        });
    });
});
