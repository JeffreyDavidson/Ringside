<?php

declare(strict_types=1);

use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\EntrantKind;

it('books a bout between two entrants of the kind as its own match type', function (EntrantKind $kind, MatchType $type): void {
    // Act
    $result = $kind->matchType();

    // Assert
    expect($result)->toBe($type);
})->with([
    'wrestler' => [EntrantKind::Wrestler, MatchType::Singles],
    'tag team' => [EntrantKind::TagTeam, MatchType::TagTeam],
]);
