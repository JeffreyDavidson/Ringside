<?php

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;

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

test('global venues remain visible while their event history follows the active promotion', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $venue = Venue::factory()->create();
    $ownedEvent = Event::factory()->for($promotion, 'promotion')->atVenue($venue)->create();
    $otherEvent = Event::factory()->for($otherPromotion, 'promotion')->atVenue($venue)->create();
    $context = app(PromotionContextService::class);

    $context->set($promotion);
    $context->enforce();

    expect(Venue::query()->findOrFail($venue->id)->is($venue))->toBeTrue()
        ->and($venue->events()->pluck('id')->all())->toBe([$ownedEvent->id]);

    $context->clear();

    expect($venue->events()->pluck('id')->all())
        ->toEqualCanonicalizing([$ownedEvent->id, $otherEvent->id]);
});
