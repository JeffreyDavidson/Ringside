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
test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
