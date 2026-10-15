<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Dom\Element;
use Dom\HTMLDocument;

use function Pest\Laravel\actingAs;

test('show pages step down one heading level at a time', function (Closure $url): void {
    // Arrange
    $administrator = administrator();

    // Act
    $html = actingAs($administrator)->get($url())->assertOk()->getContent();

    // Assert
    if (! is_string($html)) {
        throw new RuntimeException('Expected the show page to contain HTML.');
    }

    $levels = array_map(
        fn (Element $heading): int => (int) substr($heading->tagName, 1),
        iterator_to_array(HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('main h1, main h2, main h3, main h4, main h5, main h6')),
    );

    expect($levels)->not->toBeEmpty()
        ->and($levels[0])->toBe(1);

    foreach (array_slice($levels, 1, preserve_keys: true) as $index => $level) {
        expect($level - $levels[$index - 1])->toBeLessThanOrEqual(1);
    }
})->with([
    'wrestler' => [fn (): string => route('wrestlers.show', Wrestler::factory()->create())],
    'tag team' => [fn (): string => route('tag-teams.show', TagTeam::factory()->create())],
    'manager' => [fn (): string => route('managers.show', Manager::factory()->create())],
    'referee' => [fn (): string => route('referees.show', Referee::factory()->create())],
    'stable' => [fn (): string => route('stables.show', Stable::factory()->create())],
    'title' => [fn (): string => route('titles.show', Title::factory()->create())],
    'venue' => [fn (): string => route('venues.show', Venue::factory()->create())],
    'event' => [fn (): string => route('events.show', Event::factory()->create())],
    'user' => [fn (): string => route('users.show', User::factory()->create())],
]);
