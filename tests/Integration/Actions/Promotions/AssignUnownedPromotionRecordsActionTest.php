<?php

declare(strict_types=1);

use App\Actions\Promotions\AssignUnownedPromotionRecordsAction;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;

it('assigns only unowned records to the selected promotion', function (): void {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $unownedWrestler = Wrestler::factory()->create();
    $ownedWrestler = Wrestler::factory()->for($otherPromotion, 'promotion')->create();

    $assignedCount = app(AssignUnownedPromotionRecordsAction::class)
        ->handle($promotion, Wrestler::class, dryRun: false);

    $unownedWrestler->refresh();
    $ownedWrestler->refresh();

    expect($assignedCount)->toBe(1)
        ->and($unownedWrestler->promotion_id)->toBe($promotion->id)
        ->and($ownedWrestler->promotion_id)->toBe($otherPromotion->id);
});

it('does not assign records during a dry run', function (): void {
    $promotion = Promotion::factory()->create();
    $unownedWrestler = Wrestler::factory()->create();

    $unassignedCount = app(AssignUnownedPromotionRecordsAction::class)
        ->handle($promotion, Wrestler::class, dryRun: true);

    $unownedWrestler->refresh();

    expect($unassignedCount)->toBe(1)
        ->and($unownedWrestler->promotion_id)->toBeNull();
});

it('includes soft-deleted records in the dry run count and the assignment', function (): void {
    $promotion = Promotion::factory()->create();
    $live = Wrestler::factory()->create();
    $trashed = Wrestler::factory()->create();
    $trashed->delete();

    $dryRunCount = app(AssignUnownedPromotionRecordsAction::class)
        ->handle($promotion, Wrestler::class, dryRun: true);
    $assignedCount = app(AssignUnownedPromotionRecordsAction::class)
        ->handle($promotion, Wrestler::class, dryRun: false);

    expect($dryRunCount)->toBe(2)
        ->and($assignedCount)->toBe(2)
        ->and(Wrestler::query()->withTrashed()->whereNull('promotion_id')->count())->toBe(0)
        ->and($trashed->refresh()->promotion_id)->toBe($promotion->id)
        ->and($live->refresh()->promotion_id)->toBe($promotion->id);
});

it('stays idempotent once soft-deleted records are owned', function (): void {
    $promotion = Promotion::factory()->create();
    $trashed = Wrestler::factory()->create();
    $trashed->delete();
    app(AssignUnownedPromotionRecordsAction::class)->handle($promotion, Wrestler::class, dryRun: false);

    $secondRun = app(AssignUnownedPromotionRecordsAction::class)
        ->handle(Promotion::factory()->create(), Wrestler::class, dryRun: false);

    expect($secondRun)->toBe(0)
        ->and($trashed->refresh()->promotion_id)->toBe($promotion->id);
});
