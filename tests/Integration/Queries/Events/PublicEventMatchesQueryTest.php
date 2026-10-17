<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Queries\Events\PublicEventMatchesQuery;

test('reads the matches of one event in card order with the titles of its own promotion', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $wrestlers = Wrestler::factory()
        ->for($promotion)
        ->count(2)
        ->create();
    $second = EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(2)
        ->withCompetitors($wrestlers->all())
        ->create();
    $first = EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(1)
        ->create();
    EventMatch::factory()
        ->create();
    $second->titles()->attach([
        Title::factory()->create(['name' => 'Foreign Title', 'promotion_id' => Promotion::factory()->create()->id])->id,
        Title::factory()->create(['name' => 'Own Title', 'promotion_id' => $promotion->id])->id,
    ]);

    // Act
    $matches = resolve(PublicEventMatchesQuery::class)->get($event);

    // Assert
    expect($matches->modelKeys())->toBe([$first->id, $second->id])
        ->and($matches->last()?->titles->pluck('name')->all())->toBe(['Own Title'])
        ->and($matches->last()?->sides)->toHaveCount(2)
        ->and($matches->last()?->sides->first()?->competitors)->toHaveCount(1);
});

test('reads a fixed number of statements however many matches the event has', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $query = resolve(PublicEventMatchesQuery::class);
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors(Wrestler::factory()->for($promotion)->count(2)->create()->all())
        ->create();
    $one = recordStatements(fn () => $query->get($event));
    EventMatch::factory()
        ->forEvent($event)
        ->count(3)
        ->withCompetitors(Wrestler::factory()->for($promotion)->count(2)->create()->all())
        ->create();

    // Act
    $many = recordStatements(fn () => $query->get($event));

    // Assert
    expect($one)->toHaveCount(4)
        ->and($many)->toHaveCount(4);
});

test('reads the matches for a signed-in visitor from another promotion', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Owner);

    // Act
    $matches = resolve(PublicEventMatchesQuery::class)->get($event);

    // Assert
    expect(EventMatch::query()->forEventId($event->id)->count())->toBe(0)
        ->and($matches->modelKeys())->toBe([$match->id]);
});
