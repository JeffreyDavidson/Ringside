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

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('renders match history through the shared table', function (
    string $component,
    string $ownerParameter,
    Closure $arrangeMatch,
) {
    $event = Event::factory()->past()->create([
        'name' => 'Historic Match Event',
    ]);
    $owner = $arrangeMatch($event);

    $table = livewire($component, [$ownerParameter => $owner->getKey()]);

    $table
        ->assertSuccessful()
        ->assertSeeHtml('placeholder="Search matches"')
        ->assertSee('Historic Match Event');
})->with([
    'wrestler history' => [
        WrestlerPreviousMatches::class,
        'wrestlerId',
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
        static function (Event $event): Referee {
            $referee = Referee::factory()->create();
            $match = EventMatch::factory()->forEvent($event)->create();
            $match->referees()->attach($referee);

            return $referee;
        },
    ],
]);
