<?php

declare(strict_types=1);

use App\Collections\TagTeamCollection;
use App\Models\Roster\TagTeams\TagTeam;

it('is the collection a tag team query returns', function (): void {
    // Arrange
    TagTeam::factory()
        ->bookable()
        ->create();

    // Act
    $tagTeams = TagTeam::query()->get();

    // Assert
    expect($tagTeams)->toBeInstanceOf(TagTeamCollection::class);
});

it('flattens the loaded current wrestlers of every tag team', function (): void {
    // Arrange
    $first = TagTeam::factory()
        ->bookable()
        ->create();
    $second = TagTeam::factory()
        ->bookable()
        ->create();
    $expected = [
        ...$first->currentWrestlers()->pluck('wrestlers.id')->all(),
        ...$second->currentWrestlers()->pluck('wrestlers.id')->all(),
    ];
    $tagTeams = TagTeam::query()
        ->with('currentWrestlers')
        ->whereKey([$first->id, $second->id])
        ->orderBy('id')
        ->get();

    // Act
    $wrestlers = $tagTeams->currentWrestlers();

    // Assert
    expect($wrestlers->modelKeys())->toEqualCanonicalizing($expected)
        ->and($wrestlers->count())->toBe(4);
});

it('refuses to lazy load a relation that was not loaded', function (): void {
    // Arrange
    TagTeam::factory()
        ->bookable()
        ->create();
    $tagTeams = TagTeam::query()->get();

    // Act
    $read = fn () => $tagTeams->currentWrestlers();

    // Assert
    expect($read)->toThrow(LogicException::class, 'Load the currentWrestlers relation before reading it from a tag team collection.');
});

it('is empty for an empty collection', function (): void {
    // Arrange
    $tagTeams = new TagTeamCollection;

    // Act
    $wrestlers = $tagTeams->currentWrestlers();

    // Assert
    expect($wrestlers->isEmpty())->toBeTrue();
});
