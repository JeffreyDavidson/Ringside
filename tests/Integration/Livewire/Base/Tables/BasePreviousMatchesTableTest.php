<?php

declare(strict_types=1);

use App\Livewire\Referees\Tables\PreviousMatches as RefereePreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousMatches as TagTeamPreviousMatches;
use App\Livewire\Wrestlers\Tables\PreviousMatches as WrestlerPreviousMatches;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders match history through the shared table', function (
    string $component,
    string $ownerParameter,
    string $ownerRouteName,
    Closure $arrangeMatch,
): void {
    // Arrange
    $event = Event::factory()->create([
        'name' => 'Historic Match Event',
        'date' => Date::parse('2025-01-15 20:00:00'),
    ]);
    $owner = $arrangeMatch($event);

    // Act
    $table = livewire($component, [$ownerParameter => $owner->getKey()]);

    // Assert
    $table
        ->assertSuccessful()
        ->assertSeeHtml('placeholder="Search matches"')
        ->assertSee('Historic Match Event')
        ->assertSee('2025-01-15')
        ->assertSeeHtml(route('events.show', $event))
        ->assertSeeHtml(route($ownerRouteName, $owner));
})->with([
    'wrestler history' => [
        WrestlerPreviousMatches::class,
        'wrestlerId',
        'wrestlers.show',
        static function (Event $event): Wrestler {
            $wrestler = Wrestler::factory()->create();
            EventMatch::factory()
                ->forEvent($event)
                ->withCompetitors([$wrestler])
                ->create();

            return $wrestler;
        },
    ],
    'tag team history' => [
        TagTeamPreviousMatches::class,
        'tagTeamId',
        'tag-teams.show',
        static function (Event $event): TagTeam {
            $tagTeam = TagTeam::factory()->create();
            EventMatch::factory()
                ->forEvent($event)
                ->withCompetitors([$tagTeam])
                ->create();

            return $tagTeam;
        },
    ],
    'referee history' => [
        RefereePreviousMatches::class,
        'refereeId',
        'referees.show',
        static function (Event $event): Referee {
            $referee = Referee::factory()->create();
            $match = EventMatch::factory()->forEvent($event)->create();
            $match->referees()->attach($referee);

            return $referee;
        },
    ],
]);
