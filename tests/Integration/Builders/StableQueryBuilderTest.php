<?php

declare(strict_types=1);

use App\Builders\Roster\StableBuilder;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Stables\Stable;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for StableQueryBuilder query scopes and methods.
 *
 * UNIT TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Activity period filtering scopes (active, inactive, unactivated, withFutureActivation)
 * - Status-based filtering scopes (retired)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the StableQueryBuilder correctly implements
 * all query scopes for filtering stables by their various statuses.
 * Note: Stables use activity periods rather than employment for status tracking.
 *
 * @see StableBuilder
 */
describe('StableQueryBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create stables in all possible states for comprehensive scope testing
        $this->activeStable = Stable::factory()->active()->create();
        $this->futureActivatedStable = Stable::factory()->withFutureActivation()->create();
        $this->inactiveStable = Stable::factory()->inactive()->create();
        $this->retiredStable = Stable::factory()->retired()->create();
        $this->unactivatedStable = Stable::factory()->unactivated()->create();
    });

    describe('activity period scopes', function () {
        test('active stables can be retrieved', function () {
            // Act
            $activeStables = Stable::query()->established()->get();

            // Assert
            expect($activeStables)
                ->toHaveCount(1)
                ->and($activeStables->contains($this->activeStable))->toBeTrue();
        });

        test('future activated stables can be retrieved', function () {
            // Act
            $futureActivatedStables = Stable::query()->withFutureEstablishment()->get();

            // Assert
            expect($futureActivatedStables)
                ->toHaveCount(1)
                ->and($futureActivatedStables->contains($this->futureActivatedStable))->toBeTrue();
        });

        test('inactive stables can be retrieved', function () {
            // Act
            $inactiveStables = Stable::query()->disbanded()->get();

            // Assert
            expect($inactiveStables)
                ->toHaveCount(1)
                ->and($inactiveStables->contains($this->inactiveStable))->toBeTrue();
        });

        test('unactivated stables can be retrieved', function () {
            // Act
            $unactivatedStables = Stable::query()->unestablished()->get();

            // Assert
            expect($unactivatedStables)
                ->toHaveCount(1)
                ->and($unactivatedStables->contains($this->unactivatedStable))->toBeTrue();
        });
    });

    describe('status-based scopes', function () {
        test('retired stables can be retrieved', function () {
            // Act
            $retiredStables = Stable::retired()->get();

            // Assert
            expect($retiredStables)
                ->toHaveCount(1)
                ->and($retiredStables->contains($this->retiredStable))->toBeTrue();
        });
    });

    test('stables can be filtered by a member count range', function () {
        $belowRange = Stable::factory()->create();
        $withinRange = Stable::factory()->create();
        $aboveRange = Stable::factory()->create();
        $historicalOnly = Stable::factory()->create();

        $belowRange->wrestlers()->attach(Wrestler::factory()->create(), ['joined_at' => now()]);
        $withinRange->wrestlers()->attach(Wrestler::factory()->count(2)->create(), ['joined_at' => now()]);
        $aboveRange->wrestlers()->attach(Wrestler::factory()->count(3)->create(), ['joined_at' => now()]);
        $historicalOnly->wrestlers()->attach(Wrestler::factory()->count(2)->create(), [
            'joined_at' => now()->subMonth(),
            'left_at' => now()->subDay(),
        ]);

        $stables = Stable::query()->withMemberCount(2, 2)->get();

        expect($stables)
            ->toHaveCount(1)
            ->and($stables->contains($withinRange))->toBeTrue()
            ->and($stables->contains($historicalOnly))->toBeFalse();
    });

    test('availability uses the current weighted member count', function () {
        $availableStable = Stable::factory()->has(ActivityPeriod::factory(), 'activityPeriods')->create();
        $belowMinimumStable = Stable::factory()->has(ActivityPeriod::factory(), 'activityPeriods')->create();
        $historicalOnlyStable = Stable::factory()->has(ActivityPeriod::factory(), 'activityPeriods')->create();

        $availableStable->wrestlers()->attach(Wrestler::factory()->count(3)->create(), ['joined_at' => now()]);
        $belowMinimumStable->wrestlers()->attach(Wrestler::factory()->count(2)->create(), ['joined_at' => now()]);
        $historicalOnlyStable->wrestlers()->attach(Wrestler::factory()->count(3)->create(), [
            'joined_at' => now()->subMonth(),
            'left_at' => now()->subDay(),
        ]);

        $availableStables = Stable::query()->available()->get();
        $unavailableStables = Stable::query()->unavailable()->get();

        expect($availableStables->pluck('id'))
            ->toContain($availableStable->id)
            ->not->toContain($belowMinimumStable->id)
            ->not->toContain($historicalOnlyStable->id)
            ->and($unavailableStables->pluck('id'))
            ->not->toContain($availableStable->id)
            ->toContain($belowMinimumStable->id)
            ->toContain($historicalOnlyStable->id);
    });
});
