<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\DB;

test('it ends only the current championship reign', function () {
    $title = Title::factory()->create();
    $previousReign = TitleChampionship::factory()
        ->for($title)
        ->ended()
        ->create();
    $currentReign = TitleChampionship::factory()
        ->for($title)
        ->current()
        ->create();
    $endedAt = now()->startOfSecond();

    DB::transaction(function () use ($title, $endedAt): void {
        resolve(ChampionshipReignManager::class)->endCurrentReign($title, $endedAt);
    });

    expect($currentReign->refresh()->lost_at)->toEqual($endedAt)
        ->and($previousReign->refresh()->lost_at)->not->toEqual($endedAt);
});

test('it locks the current reign before ending it', function () {
    // Arrange
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->current()->create();

    // Act
    $statements = recordStatements(fn () => DB::transaction(
        fn () => resolve(ChampionshipReignManager::class)->endCurrentReign($title, now())
    ));

    // Assert
    $reignLock = array_find_key(
        $statements,
        fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "titles_championships"'),
    );
    $reignUpdate = array_find_key($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'update "titles_championships"'));

    expect($reignLock)->not->toBeNull()
        ->and($statements[$reignLock]['bindings'][0] ?? null)->toBe($title->id)
        ->and($reignLock)->toBeLessThan($reignUpdate);
});

test('ending a vacant championship is a no-op', function () {
    $title = Title::factory()->create();

    DB::transaction(function () use ($title): void {
        resolve(ChampionshipReignManager::class)->endCurrentReign($title, now());
    });

    expect($title->championships()->doesntExist())->toBeTrue();
});

test('it never ends the current reign before it began', function () {
    $title = Title::factory()->create();
    $reign = TitleChampionship::factory()->for($title)->current()->create(['won_at' => now()->addDays(10)]);

    DB::transaction(function () use ($title): void {
        resolve(ChampionshipReignManager::class)->endCurrentReign($title, now());
    });

    expect($reign->refresh()->lost_at?->toDateTimeString())->toBe($reign->won_at->toDateTimeString());
});

test('it never ends a champions reigns before they began', function () {
    $champion = Wrestler::factory()->create();
    $futureReign = TitleChampionship::factory()->forWrestler($champion)->current()->create(['won_at' => now()->addDays(10)]);
    $pastReign = TitleChampionship::factory()->forWrestler($champion)->current()->create(['won_at' => now()->subDays(10)]);

    DB::transaction(function () use ($champion): void {
        resolve(ChampionshipReignManager::class)->endCurrentReignsForChampion($champion, now());
    });

    expect($futureReign->refresh()->lost_at?->toDateTimeString())->toBe($futureReign->won_at->toDateTimeString())
        ->and($pastReign->refresh()->lost_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it rejects reconciling a title change for an event without a date', function () {
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $match = EventMatch::factory()->for(Event::factory()->unscheduled())->create();
    $match->setRelation('event', $match->event);

    $reconcile = fn () => resolve(ChampionshipReignManager::class)->reconcileMatchOutcome(
        $match,
        $title,
        Wrestler::factory()->create(),
        TitleChampionship::query()->get(),
    );

    expect($reconcile)->toThrow(InvalidMatchOutcomeException::class, 'A title change cannot be recorded for an event without a date.');
});

test('a result with no winner never changes the champion', function () {
    // Arrange
    $title = Title::factory()->active()->create();
    $match = EventMatch::factory()->create();
    TitleChampionship::factory()->for($title)->forWrestler(Wrestler::factory()->create())->current()->create();

    // Act
    $changes = resolve(ChampionshipReignManager::class)->changesChampion(
        $match,
        $title,
        null,
        $title->championships()->get(),
    );

    // Assert
    expect($changes)->toBeFalse();
});
