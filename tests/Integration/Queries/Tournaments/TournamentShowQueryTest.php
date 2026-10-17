<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Models\Titles\Title;
use App\Queries\Tournaments\TournamentShowQuery;

use function Pest\Laravel\actingAs;

test('reads the entries and the booking choices of the tournament promotion in order', function (): void {
    // Arrange
    actingAs(administrator());
    $world = tournamentFixture();
    $promotion = $world['promotion'];
    $latest = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addDay()]);
    Event::factory()
        ->for(Promotion::factory())
        ->create(['date' => now()->addDays(2)]);
    $zulu = Title::factory()
        ->for($promotion)
        ->create(['name' => 'Zulu Title']);
    $alpha = Title::factory()
        ->for($promotion)
        ->create(['name' => 'Alpha Title']);
    Title::factory()
        ->for(Promotion::factory())
        ->create(['name' => 'Foreign Title']);
    $second = MatchStipulation::factory()
        ->create(['name' => 'Zebra Rules']);
    $first = MatchStipulation::factory()
        ->create(['name' => 'Anything Goes']);

    // Act
    $read = resolve(TournamentShowQuery::class)->get($world['tournament']);

    // Assert
    expect($read['entries']->pluck('seed')->all())->toBe([1, 2, 3, 4])
        ->and($read['entries']->every(fn ($entry): bool => $entry->relationLoaded('competitor')))->toBeTrue()
        ->and($read['events']->firstOrFail()->id)->toBe($latest->id)
        ->and($read['events']->pluck('promotion_id')->unique()->all())->toBe([$promotion->id])
        ->and($read['titles']->pluck('id')->all())->toBe([$alpha->id, $zulu->id])
        ->and($read['stipulations']->pluck('id')->all())->toBe([$first->id, $second->id]);
});

test('lists only bookable competitors as available', function (): void {
    // Arrange
    actingAs(administrator());
    $world = tournamentFixture();
    $entries = $world['tournament']->entries()
        ->with('competitor')
        ->orderBy('seed')
        ->get();
    $entries->firstOrFail()->competitor->delete();

    // Act
    $read = resolve(TournamentShowQuery::class)->get($world['tournament']);

    // Assert
    expect($read['availableIds'])->toEqualCanonicalizing($entries->skip(1)->pluck('competitor_id')->all());
});
