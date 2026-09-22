<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Route;

function attachActivePromotion(User $user, Promotion $promotion): void
{
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
}

test('promotion middleware establishes the selected active promotion', function () {
    $user = User::factory()->administrator()->create();
    $firstPromotion = Promotion::factory()->create();
    $secondPromotion = Promotion::factory()->create();
    attachActivePromotion($user, $firstPromotion);
    attachActivePromotion($user, $secondPromotion);

    Route::middleware(['web', 'promotion.context'])->get('/promotion-context-test', fn () => response()->json([
        'promotion_id' => app(PromotionContextService::class)->required()->id,
    ]));

    $response = $this
        ->withSession(['active_promotion_id' => $secondPromotion->id])
        ->actingAs($user)
        ->get('/promotion-context-test');

    $response
        ->assertOk()
        ->assertJson(['promotion_id' => $secondPromotion->id]);
});

test('promotion-scoped models only return records from the active promotion', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $ownedEvent = Event::factory()->for($promotion, 'promotion')->create();
    $otherEvent = Event::factory()->for($otherPromotion, 'promotion')->create();
    $ownedWrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
    $otherWrestler = Wrestler::factory()->for($otherPromotion, 'promotion')->create();
    $context = app(PromotionContextService::class);

    $context->set($promotion);
    $context->enforce();

    expect(Event::query()->pluck('id')->all())
        ->toBe([$ownedEvent->id])
        ->and(Wrestler::query()->pluck('id')->all())
        ->toBe([$ownedWrestler->id]);

    $context->clear();

    expect(Event::query()->pluck('id')->all())
        ->toContain($otherEvent->id)
        ->and(Wrestler::query()->pluck('id')->all())
        ->toContain($otherWrestler->id);
});

test('promotion middleware rejects users without an active membership', function () {
    $user = User::factory()->basicUser()->create();

    Route::middleware(['web', 'promotion.context'])->get('/promotion-context-test', fn () => response()->noContent());

    $this
        ->actingAs($user)
        ->get('/promotion-context-test')
        ->assertForbidden();
});
