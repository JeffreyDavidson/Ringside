<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\ViewModels\Tournaments\TournamentShowViewModel;

use function Pest\Laravel\actingAs;

test('builds the entries and the booking choices of the tournament promotion in order', function (): void {
    // Arrange
    actingAs(administrator());
    $world = tournamentFixture();
    $promotion = $world['promotion'];
    $entries = $world['tournament']->entries()->get();
    TournamentEntry::query()->whereKey($entries->firstOrFail()->id)->toBase()->update(['seed' => 9]);
    TournamentEntry::query()->whereKey($entries->last()?->id)->toBase()->update(['seed' => 0]);
    $soonest = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addDay()]);
    $tiedFirst = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addDays(3)]);
    $tiedSecond = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addDays(3)]);
    $latest = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addDays(5)]);
    Event::factory()
        ->for(Promotion::factory())
        ->create(['date' => now()->addDays(6)]);
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
    $page = resolve(TournamentShowViewModel::class)->data($world['tournament']);

    // Assert
    expect($page['entries']->pluck('seed')->all())->toBe([0, 2, 3, 9])
        ->and($page['entries']->every(fn ($entry): bool => $entry->relationLoaded('competitor')))->toBeTrue()
        ->and($page['events']->pluck('id')->all())->toBe([$latest->id, $tiedFirst->id, $tiedSecond->id, $soonest->id, $world['event']->id])
        ->and($page['titles']->pluck('id')->all())->toBe([$alpha->id, $zulu->id])
        ->and($page['stipulations']->pluck('id')->all())->toBe([$first->id, $second->id]);
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
    $page = resolve(TournamentShowViewModel::class)->data($world['tournament']);

    // Assert
    expect($page['availableIds'])->toEqualCanonicalizing($entries->skip(1)->pluck('competitor_id')->all());
});

test('returns empty choices for a promotion with nothing to book with', function (): void {
    // Arrange
    actingAs(administrator());
    $tournament = Tournament::factory()
        ->create();

    // Act
    $page = resolve(TournamentShowViewModel::class)->data($tournament);

    // Assert
    expect($page['entries'])->toBeEmpty()
        ->and($page['availableIds'])->toBeEmpty()
        ->and($page['events'])->toBeEmpty()
        ->and($page['titles'])->toBeEmpty()
        ->and($page['stipulations'])->toBeEmpty();
});
