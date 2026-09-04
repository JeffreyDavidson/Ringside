<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @group matches
 * @group integration
 * @group livewire
 * @group tables
 */
beforeEach(function () {
    $this->admin = administrator();
    actingAs($this->admin);
});

function attachTableCompetitor(EventMatch $match, Wrestler|TagTeam $competitor, int $position): void
{
    $side = $match->sides()->firstOrCreate(compact('position'));

    MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
        'competitor_type' => $competitor->getMorphClass(),
        'competitor_id' => $competitor->id,
    ]);
}

describe('MatchesTable Rendering', function () {
    it('displays an empty state when the event has no matches', function () {
        $event = Event::factory()->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('No records found.');
    })->group('matches', 'integration', 'livewire', 'tables', 'rendering');

    it('displays matches in table', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()
            ->for($event)
            ->state(['match_type' => MatchType::Singles])
            ->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles');
    });

    it('offers result recording and correction from the table', function () {
        $event = Event::factory()->create();
        $unresultedMatch = EventMatch::factory()->for($event)->create();
        $resultedMatch = EventMatch::factory()->for($event)->create([
            'match_finish' => MatchFinish::TimeLimitDraw,
        ]);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Record Result')
            ->assertSee('Correct Result')
            ->assertSeeHtml("matchId: {$unresultedMatch->id}")
            ->assertSeeHtml("matchId: {$resultedMatch->id}");
    });

    it('displays match competitors', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $wrestler1 = Wrestler::factory()->create(['name' => 'John Cena']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);

        attachTableCompetitor($match, $wrestler1, 1);
        attachTableCompetitor($match, $wrestler2, 2);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('John Cena')
            ->assertSee('The Rock');
    });

    it('displays match referees', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $referee = Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);

        $match->referees()->attach($referee);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Earl Hebner');
    });

    it('eager loads the relationships used to render each match', function () {
        $event = Event::factory()->create();
        $eventMatch = EventMatch::factory()->for($event)->create();
        $wrestler = Wrestler::factory()->create();
        attachTableCompetitor($eventMatch, $wrestler, 1);
        $table = app(MatchesTable::class);
        $table->eventId = $event->id;

        $match = $table->builder()->firstOrFail();
        $competitor = $match->competitors->firstOrFail();

        expect($match->relationLoaded('event'))->toBeTrue()
            ->and($match->relationLoaded('referees'))->toBeTrue()
            ->and($match->relationLoaded('titles'))->toBeTrue()
            ->and($match->relationLoaded('competitors'))->toBeTrue()
            ->and($match->relationLoaded('winningSide'))->toBeTrue()
            ->and($competitor->relationLoaded('competitor'))->toBeTrue()
            ->and($competitor->relationLoaded('side'))->toBeTrue();
    });

    it('displays championship titles', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $title = Title::factory()->create(['name' => 'WWE Championship']);

        $match->titles()->attach($title);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('WWE Championship');
    });
});

describe('MatchesTable Search and Filtering', function () {
    it('can search matches by match type', function () {
        $event = Event::factory()->create();

        EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create();
        EventMatch::factory()->for($event)->state(['match_type' => MatchType::TagTeam])->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->set('search', 'Singles')
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');
    });

    it('can filter matches by event', function () {
        $event1 = Event::factory()->create(['name' => 'WrestleMania']);
        $event2 = Event::factory()->create(['name' => 'SummerSlam']);

        EventMatch::factory()->for($event1)->state(['match_type' => MatchType::Singles])->create();
        EventMatch::factory()->for($event2)->state(['match_type' => MatchType::TagTeam])->create();

        livewire(MatchesTable::class, ['eventId' => $event1->id])
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');
    });
});

describe('MatchesTable Complex Relationships', function () {
    it('displays matches with multiple competitors', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $wrestler1 = Wrestler::factory()->create(['name' => 'Wrestler One']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'Wrestler Two']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);

        attachTableCompetitor($match, $wrestler1, 1);
        attachTableCompetitor($match, $wrestler2, 2);
        attachTableCompetitor($match, $tagTeam, 3);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Wrestler One')
            ->assertSee('Wrestler Two')
            ->assertSee('Tag Team')
            ->assertSeeHtml(route('wrestlers.show', $wrestler1))
            ->assertSeeHtml(route('tag-teams.show', $tagTeam));
    });
});

describe('MatchesTable Pagination', function () {
    it('paginates the event matches using the selected page size', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->count(6)->create();

        $table = livewire(MatchesTable::class, ['eventId' => $event->id])
            ->set('perPage', 5);
        $firstPage = $table->viewData('rows');

        expect($firstPage)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($firstPage->currentPage())->toBe(1)
            ->and($firstPage->count())->toBe(5)
            ->and($firstPage->total())->toBe(6);

        $table->call('nextPage');
        $secondPage = $table->viewData('rows');

        expect($secondPage)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($secondPage->currentPage())->toBe(2)
            ->and($secondPage->count())->toBe(1)
            ->and($secondPage->total())->toBe(6);
    });
});

describe('MatchesTable Authorization', function () {
    it('authorizes the selected event instance', function () {
        $event = Event::factory()->create();
        $authorizedEvent = null;

        Gate::before(function ($user, string $ability, array $arguments) use (&$authorizedEvent): ?bool {
            if ($ability !== 'view' || ! ($arguments[0] ?? null) instanceof Event) {
                return null;
            }

            $authorizedEvent = $arguments[0];

            return true;
        });
        actingAs(basicUser());

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSuccessful();

        expect($authorizedEvent?->is($event))->toBeTrue();
    });

    it('requires authentication', function () {
        $event = Event::factory()->create();
        Auth::logout();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertForbidden();
    });

    it('requires administrator privileges', function () {
        $event = Event::factory()->create();
        $basicUser = basicUser();
        actingAs($basicUser);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertForbidden();
    });
});
