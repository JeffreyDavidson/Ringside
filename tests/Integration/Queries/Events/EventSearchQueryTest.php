<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Queries\Events\EventSearchQuery;

test('finds the promotion events whose name contains the term, newest first', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $older = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Summer Slam', 'date' => now()->addDays(2)]);
    $newer = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Slam Finale', 'date' => now()->addDays(9)]);
    $unscheduled = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Slam Someday', 'date' => null]);
    Event::factory()
        ->for($promotion)
        ->create(['name' => 'Winter Brawl', 'date' => now()->addDays(5)]);

    // Act
    $found = resolve(EventSearchQuery::class)->get('slam', $promotion->id);

    // Assert
    expect($found->modelKeys())->toBe([$newer->id, $older->id, $unscheduled->id]);
});

test('lists the newest events of the promotion for an empty term and treats wildcards literally', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $plain = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Plain Show']);
    $percent = Event::factory()
        ->for($promotion)
        ->create(['name' => '100% Show']);

    // Act
    $all = resolve(EventSearchQuery::class)->get('', $promotion->id);
    $wildcard = resolve(EventSearchQuery::class)->get('%', $promotion->id);

    // Assert
    expect($all->modelKeys())->toEqualCanonicalizing([$plain->id, $percent->id])
        ->and($wildcard->modelKeys())->toEqualCanonicalizing([$plain->id, $percent->id]);
});

test('limits the results to twenty and leaves out other promotions and deleted events', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    Event::factory()
        ->for($promotion)
        ->count(21)
        ->create();
    Event::factory()
        ->for(Promotion::factory())
        ->create(['name' => 'Foreign Show']);
    Event::factory()
        ->for($promotion)
        ->create(['name' => 'Deleted Show'])
        ->delete();

    // Act
    $found = resolve(EventSearchQuery::class)->get('', $promotion->id);
    $foreign = resolve(EventSearchQuery::class)->get('Foreign', $promotion->id);
    $deleted = resolve(EventSearchQuery::class)->get('Deleted', $promotion->id);

    // Assert
    expect($found)->toHaveCount(20)
        ->and($foreign)->toBeEmpty()
        ->and($deleted)->toBeEmpty();
});
