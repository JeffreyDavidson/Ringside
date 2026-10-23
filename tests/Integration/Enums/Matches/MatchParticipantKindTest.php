<?php

declare(strict_types=1);

use App\Enums\Matches\MatchParticipantKind;

test('it labels each participant kind in the singular', function (MatchParticipantKind $kind, string $label) {
    // Act
    $result = $kind->label();

    // Assert
    expect($result)->toBe($label);
})->with([
    'wrestler' => [MatchParticipantKind::Wrestler, 'Wrestler'],
    'tag team' => [MatchParticipantKind::TagTeam, 'Tag team'],
    'referee' => [MatchParticipantKind::Referee, 'Referee'],
    'title' => [MatchParticipantKind::Title, 'Title'],
]);

test('it labels each participant kind in the plural', function (MatchParticipantKind $kind, string $label) {
    // Act
    $result = $kind->pluralLabel();

    // Assert
    expect($result)->toBe($label);
})->with([
    'wrestlers' => [MatchParticipantKind::Wrestler, 'wrestlers'],
    'tag teams' => [MatchParticipantKind::TagTeam, 'tag teams'],
    'referees' => [MatchParticipantKind::Referee, 'referees'],
    'titles' => [MatchParticipantKind::Title, 'titles'],
]);
