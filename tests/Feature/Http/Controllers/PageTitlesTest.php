<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

use function Pest\Laravel\actingAs;

describe('browser tab titles', function (): void {
    it('names each promoter page in the browser tab', function (Closure $url, string $pageTitle): void {
        // Arrange
        $user = administrator();

        // Act
        $response = actingAs($user)->get($url());

        // Assert
        $response
            ->assertOk()
            ->assertSeeHtml("<title>{$pageTitle} · Ringside</title>");
    })->with([
        'dashboard' => [fn (): string => route('dashboard'), 'Overview'],
        'wrestlers' => [fn (): string => route('wrestlers.index'), 'Wrestlers'],
        'tag teams' => [fn (): string => route('tag-teams.index'), 'Tag Teams'],
        'managers' => [fn (): string => route('managers.index'), 'Managers'],
        'referees' => [fn (): string => route('referees.index'), 'Referees'],
        'stables' => [fn (): string => route('stables.index'), 'Stables'],
        'titles' => [fn (): string => route('titles.index'), 'Titles'],
        'events' => [fn (): string => route('events.index'), 'Events'],
        'venues' => [fn (): string => route('venues.index'), 'Venues'],
        'promotions' => [fn (): string => route('promotions.index'), 'Promotions'],
        'users' => [fn (): string => route('users.index'), 'Users'],
        'wrestler' => [fn (): string => route('wrestlers.show', Wrestler::factory()->create(['name' => 'Marcus Vale'])), 'Marcus Vale'],
        'event' => [fn (): string => route('events.show', Event::factory()->create(['name' => 'Fall Brawl'])), 'Fall Brawl'],
        'title' => [fn (): string => route('titles.show', Title::factory()->create(['name' => 'Heavyweight Championship'])), 'Heavyweight Championship'],
    ]);

    it('escapes page names in the browser tab', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => "Shane O'Neil & Co"]);

        // Act
        $response = actingAs(administrator())->get(route('wrestlers.show', $wrestler));

        // Assert
        $response->assertSeeHtml('<title>Shane O&#039;Neil &amp; Co · Ringside</title>');
    });
});
