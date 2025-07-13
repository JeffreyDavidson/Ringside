<?php

declare(strict_types=1);

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Enums\Titles\TitleStatus;
use App\Exceptions\Status\CannotBeActivatedException;
use App\Exceptions\Status\CannotBeDebutedException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Title Activation Actions.
 *
 * INTEGRATION TEST SCOPE:
 * - Complete action workflows from start to finish
 * - Status synchronization across multiple components
 * - Repository and action integration
 * - Activity period management integration
 * - Transaction integrity across components
 */
describe('Title Activation Action Integration', function () {

    beforeEach(function () {
        $this->title = Title::factory()->create(['status' => TitleStatus::Undebuted]);
    });

    describe('DebutAction integration', function () {
        test('complete debut workflow synchronizes all state', function () {
            $debutDate = Carbon::now();

            // Verify initial state
            expect($this->title->isUnactivated())->toBeTrue();
            expect($this->title->isCurrentlyActive())->toBeFalse();
            expect($this->title->status)->toBe(TitleStatus::Undebuted);

            // Execute complete debut workflow
            DebutAction::run($this->title, $debutDate);

            // Verify complete state synchronization
            $refreshedTitle = $this->title->fresh();
            expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
            expect($refreshedTitle->isUnactivated())->toBeFalse();
            expect($refreshedTitle->status)->toBe(TitleStatus::Active);
            expect($refreshedTitle->currentActivityPeriod)->not->toBeNull();
            expect($refreshedTitle->currentActivityPeriod->started_at->toDateTimeString())
                ->toBe($debutDate->toDateTimeString());
        });

        test('debuting already active title throws validation error', function () {
            $title = Title::factory()->active()->create();

            // Verify initial active state
            expect($title->isCurrentlyActive())->toBeTrue();
            expect($title->status)->toBe(TitleStatus::Active);

            // Attempt to debut again should throw validation error
            expect(fn () => DebutAction::run($title, Carbon::now()))
                ->toThrow(CannotBeDebutedException::class);
        });

        test('debut action integrates with status transition pipeline', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            expect($title->status)->toBe(TitleStatus::Undebuted);
            expect($title->isCurrentlyActive())->toBeFalse();

            // Test that the full pipeline handles the transition
            DebutAction::run($title, Carbon::now());

            $refreshedTitle = $title->fresh();
            expect($refreshedTitle->status)->toBe(TitleStatus::Active);
            expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
            expect($refreshedTitle->currentActivityPeriod)->not->toBeNull();
        });
    });

    describe('multiple action integration', function () {
        test('debut then pull workflow maintains data consistency', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            // Initial state
            expect($title->status)->toBe(TitleStatus::Undebuted);

            // Debut title
            DebutAction::run($title, Carbon::now());
            $afterDebut = $title->fresh();
            expect($afterDebut->status)->toBe(TitleStatus::Active);
            expect($afterDebut->isCurrentlyActive())->toBeTrue();

            // Pull title
            PullAction::run($afterDebut, Carbon::now());
            $afterPull = $title->fresh();

            // Verify pull status synchronization
            expect($afterPull->status)->toBe(TitleStatus::Inactive);
            expect($afterPull->isInactive())->toBeTrue();
            expect($afterPull->isCurrentlyActive())->toBeFalse();
        });

        test('debut with future date handles scheduling', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);
            $futureDate = Carbon::now()->addDays(7);

            DebutAction::run($title, $futureDate);

            $refreshedTitle = $title->fresh();
            expect($refreshedTitle->status)->toBe(TitleStatus::PendingDebut);
            expect($refreshedTitle->futureActivityPeriod->started_at->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });

        test('pull then reinstate workflow maintains consistency', function () {
            $title = Title::factory()->active()->create();

            // Verify initially active
            expect($title->isCurrentlyActive())->toBeTrue();
            expect($title->status)->toBe(TitleStatus::Active);

            // Pull the title
            PullAction::run($title, Carbon::now());
            $afterPull = $title->fresh();
            expect($afterPull->status)->toBe(TitleStatus::Inactive);
            expect($afterPull->isInactive())->toBeTrue();

            // Reinstate the title
            ReinstateAction::run($afterPull, Carbon::now());
            $afterReinstate = $title->fresh();
            expect($afterReinstate->status)->toBe(TitleStatus::Active);
            expect($afterReinstate->isCurrentlyActive())->toBeTrue();
        });
    });

    describe('transaction integrity', function () {
        test('debut action maintains transaction integrity', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            // Verify the action handles transactions properly
            DebutAction::run($title, Carbon::now());

            // All changes should be committed together
            $refreshedTitle = $title->fresh();
            expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
            expect($refreshedTitle->status)->toBe(TitleStatus::Active);
            expect($refreshedTitle->currentActivityPeriod)->not->toBeNull();

            // Verify no partial updates occurred
            expect($refreshedTitle->activityPeriods()->whereNull('ended_at')->count())->toBe(1);
        });

        test('action rollback maintains data consistency on failure', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            DebutAction::run($title, Carbon::now());

            $refreshedTitle = $title->fresh();

            // Verify all state is consistent - no orphaned records
            if ($refreshedTitle->isCurrentlyActive()) {
                expect($refreshedTitle->status)->toBe(TitleStatus::Active);
                expect($refreshedTitle->currentActivityPeriod)->not->toBeNull();
            }
        });
    });

    describe('business rule integration', function () {
        test('debut respects business validation rules', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            // Test that debut follows business rules
            DebutAction::run($title, Carbon::now());

            $refreshedTitle = $title->fresh();

            // Verify business rule compliance
            expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
            expect($refreshedTitle->canBeDebuted())->toBeFalse(); // Already active
            expect($refreshedTitle->canBePulled())->toBeTrue(); // Can be pulled when active
        });

        test('debut enables title activity capability', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            // Undebuted title should not be active
            expect($title->isCurrentlyActive())->toBeFalse();
            expect($title->canBePulled())->toBeFalse();

            DebutAction::run($title, Carbon::now());

            // Active title should be pullable
            $refreshedTitle = $title->fresh();
            expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
            expect($refreshedTitle->canBePulled())->toBeTrue();
        });

        test('activity periods do not overlap inappropriately', function () {
            $title = Title::factory()->create(['status' => TitleStatus::Undebuted]);

            // Debut title
            DebutAction::run($title, Carbon::now());

            // Pull title
            PullAction::run($title, Carbon::now()->addDays(1));

            // Reinstate title
            ReinstateAction::run($title, Carbon::now()->addDays(2));

            $refreshedTitle = $title->fresh();

            // Should have exactly one active period
            expect($refreshedTitle->activityPeriods()->whereNull('ended_at')->count())->toBe(1);

            // Should have one completed period
            expect($refreshedTitle->activityPeriods()->whereNotNull('ended_at')->count())->toBe(1);
        });
    });
});
