<?php

declare(strict_types=1);

use App\Livewire\Matches\Modals\FormModal;

it('returns match types keyed by their stored values', function (): void {
    // Arrange
    $modal = app(FormModal::class);

    // Act
    $matchTypes = $modal->getMatchTypes();

    // Assert
    expect($matchTypes)->toBe([
        'singles' => 'Singles',
        'tag-team' => 'Tag Team',
        'triple-threat' => 'Triple Threat',
        'triangle' => 'Triangle',
        'fatal-4-way' => 'Fatal 4 Way',
        '6-man-tag-team' => '6 Man Tag Team',
        '8-man-tag-team' => '8 Man Tag Team',
        '10-man-tag-team' => '10 Man Tag Team',
        'two-on-one-handicap' => 'Two On One Handicap',
        'three-on-two-handicap' => 'Three On Two Handicap',
        'battle-royal' => 'Battle Royal',
        'royal-rumble' => 'Royal Rumble',
        'tornado-tag-team' => 'Tornado Tag Team',
        'gauntlet' => 'Gauntlet',
    ]);
});
