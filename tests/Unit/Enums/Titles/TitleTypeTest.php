<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;

it('defines the competitor input mapping for each title type', function (
    TitleType $titleType,
    string $inputKey,
    string $label,
    string $opposingInputKey,
) {
    expect($titleType->competitorInputKey())->toBe($inputKey)
        ->and($titleType->competitorLabel())->toBe($label)
        ->and($titleType->opposingCompetitorInputKey())->toBe($opposingInputKey);
})->with([
    'singles' => [TitleType::Singles, 'wrestlers', 'wrestlers', 'tag_teams'],
    'tag team' => [TitleType::TagTeam, 'tag_teams', 'tag teams', 'wrestlers'],
]);

it('resolves a title type from its champion morph class', function (TitleType $titleType) {
    expect(TitleType::tryFromChampionMorphClass($titleType->championMorphClass()))
        ->toBe($titleType);
})->with(TitleType::cases());

it('returns null for an unknown champion morph class', function () {
    expect(TitleType::tryFromChampionMorphClass('unknown'))->toBeNull();
});
