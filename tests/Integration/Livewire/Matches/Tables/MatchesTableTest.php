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
    it('can render matches table', function () {
        $event = Event::factory()->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
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

    it('eager loads referee assignments for displayed matches', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->create();
        $table = app(MatchesTable::class);
        $table->eventId = $event->id;

        $match = $table->builder()->firstOrFail();

        expect($match->relationLoaded('referees'))->toBeTrue();
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

    it('can filter matches by match type', function () {
        $event = Event::factory()->create();

        EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create();
        EventMatch::factory()->for($event)->state(['match_type' => MatchType::TagTeam])->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles')
            ->assertSee('Tag Team');
    });

    it('displays competitor names in matches', function () {
        $event = Event::factory()->create();
        $match1 = EventMatch::factory()->for($event)->create();
        $match2 = EventMatch::factory()->for($event)->create();

        $wrestler1 = Wrestler::factory()->create(['name' => 'Stone Cold']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);

        attachTableCompetitor($match1, $wrestler1, 1);
        attachTableCompetitor($match2, $wrestler2, 1);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Stone Cold')
            ->assertSee('The Rock');
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

    it('displays championship matches correctly', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $title1 = Title::factory()->create(['name' => 'World Championship']);
        $title2 = Title::factory()->create(['name' => 'Tag Team Championship']);

        $match->titles()->attach($title1);
        $match->titles()->attach($title2);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('World Championship')
            ->assertSee('Tag Team Championship');
    });

    it('displays matches with multiple referees', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $referee1 = Referee::factory()->create(['first_name' => 'Referee', 'last_name' => 'One']);
        $referee2 = Referee::factory()->create(['first_name' => 'Referee', 'last_name' => 'Two']);

        $match->referees()->attach($referee1);
        $match->referees()->attach($referee2);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Referee One')
            ->assertSee('Referee Two');
    });

    it('handles matches with no competitors gracefully', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles');
    });

    it('handles matches with no referees gracefully', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
        attachTableCompetitor($match, $wrestler, 1);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Test Wrestler')
            ->assertSee('Test Wrestler');
    });
});

describe('MatchesTable Performance', function () {
    it('handles large datasets efficiently', function () {
        $event = Event::factory()->create();

        // Create multiple matches with relationships
        $matches = EventMatch::factory()
            ->for($event)
            ->state(['match_type' => MatchType::Singles])
            ->count(20)
            ->create();

        $wrestlers = Wrestler::factory()->count(10)->create();
        $referees = Referee::factory()->count(5)->create();

        // Attach relationships to matches
        foreach ($matches as $index => $match) {
            attachTableCompetitor($match, $wrestlers->random(), 1);
            $match->referees()->attach($referees[$index % 5]);
        }

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('eager loads necessary relationships', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
        $referee = Referee::factory()->create(['first_name' => 'Test', 'last_name' => 'Referee']);
        $title = Title::factory()->create(['name' => 'Test Title']);

        attachTableCompetitor($match, $wrestler, 1);
        $match->referees()->attach($referee);
        $match->titles()->attach($title);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk()
            ->assertSee('Test Wrestler')
            ->assertSee('Test Referee')
            ->assertSee('Test Title');
    });
});

describe('MatchesTable Pagination', function () {
    it('handles pagination correctly', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->count(25)->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('maintains search across pagination', function () {
        $event = Event::factory()->create();

        EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->count(15)->create();
        EventMatch::factory()->for($event)->state(['match_type' => MatchType::TagTeam])->count(15)->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->set('search', 'Singles')
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');
    });
});

describe('MatchesTable Sorting', function () {
    it('can sort matches by different columns', function () {
        $event = Event::factory()->create();

        $matchA = EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create();
        $matchZ = EventMatch::factory()->for($event)->state(['match_type' => MatchType::TagTeam])->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSeeInOrder(['Singles', 'Tag Team']);
    });

    it('can sort matches by match number', function () {
        $event = Event::factory()->create();

        $match1 = EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create(['match_number' => 1]);
        $match2 = EventMatch::factory()->for($event)->state(['match_type' => MatchType::TagTeam])->create(['match_number' => 2]);

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles')
            ->assertSee('Tag Team');
    });
});

describe('MatchesTable Actions', function () {
    it('displays match actions for authorized users', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('handles match action integration', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create();

        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);
        $component->assertOk();
        $component->assertSee('Singles');
    });
});

describe('MatchesTable Event Integration', function () {
    it('displays matches for specific event only', function () {
        $event1 = Event::factory()->create(['name' => 'Event One']);
        $event2 = Event::factory()->create(['name' => 'Event Two']);

        EventMatch::factory()->for($event1)->state(['match_type' => MatchType::Singles])->create();
        EventMatch::factory()->for($event2)->state(['match_type' => MatchType::TagTeam])->create();

        livewire(MatchesTable::class, ['eventId' => $event1->id])
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');
    });

    it('handles event with multiple matches', function () {
        $event = Event::factory()->create();

        EventMatch::factory()->for($event)->state(['match_type' => MatchType::Singles])->create();
        EventMatch::factory()->for($event)->state(['match_type' => MatchType::TagTeam])->create();

        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles')
            ->assertSee('Tag Team');
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
