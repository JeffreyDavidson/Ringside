<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Matches\Support\MatchCompetitorRouteResolver;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Lifecycle\Injury;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

function actingInPromotion(Promotion $promotion, MembershipRole $role): void
{
    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => $role->value,
        'status' => MembershipStatus::Active->value,
    ]);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
}

describe('rendering', function (): void {
    it('renders an empty state when the event has no matches', function (): void {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Matches')
            ->assertSee('No matches yet.')
            ->assertDontSeeHtml('placeholder="Search matches"');
    });

    it('renders the match competitors, referees, titles, and empty result', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $wrestler = Wrestler::factory()->create(['name' => 'Singles Wrestler']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);
        $referee = Referee::factory()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $title = Title::factory()
            ->tagTeam()
            ->create(['name' => 'World Tag Team Titles']);
        $match = EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::TagTeam)
            ->withCompetitors([$wrestler, $tagTeam])
            ->create();
        $match->referees()->attach($referee);
        $match->titles()->attach($title);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee(MatchType::TagTeam->label())
            ->assertSee('Singles Wrestler')
            ->assertSee('Tag Team')
            ->assertSee('Earl Hebner')
            ->assertSee('World Tag Team Titles')
            ->assertSee('N/A')
            ->assertSeeHtml(route('wrestlers.show', $wrestler))
            ->assertSeeHtml(route('tag-teams.show', $tagTeam))
            ->assertSeeHtml(route('referees.show', $referee))
            ->assertSeeHtml(route('titles.show', $title));
    });

    it('offers result recording and correction for the appropriate matches', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $unresultedMatch = EventMatch::factory()
            ->forEvent($event)
            ->create();
        $resultedMatch = EventMatch::factory()
            ->forEvent($event)
            ->create(['match_finish' => MatchFinish::TimeLimitDraw]);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSee('Record Result')
            ->assertSee('Correct Result')
            ->assertSeeHtml("matchId: {$unresultedMatch->id}")
            ->assertSeeHtml("matchId: {$resultedMatch->id}");
    });

    it('offers editing only for matches without a recorded result', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $editableMatch = EventMatch::factory()->forEvent($event)->create();
        $completedMatch = EventMatch::factory()->forEvent($event)->create([
            'match_finish' => MatchFinish::TimeLimitDraw,
        ]);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSeeHtml('aria-label="More actions for match '.$editableMatch->match_number.'"')
            ->assertSeeHtml('data-test="match-edit-action"')
            ->assertSeeHtml('data-match-id="'.$editableMatch->id.'"')
            ->assertDontSeeHtml('data-match-id="'.$completedMatch->id.'"')
            ->assertSeeHtml("eventId: {$event->id}, modelId: {$editableMatch->id}");
    });

    it('hides match editing from promotion members without update access', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        $match = EventMatch::factory()->forEvent($event)->create();
        $member = basicUser();
        $promotion->users()->attach($member, [
            'role' => MembershipRole::Member->value,
            'status' => MembershipStatus::Active->value,
        ]);
        actingAs($member);
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee($match->match_type->label())
            ->assertDontSeeHtml('data-test="match-edit-action"');
    });
});

describe('unbookable booked members', function (): void {
    it('marks competitors who can no longer be booked', function (string $state): void {
        // Arrange
        $event = Event::factory()->scheduled()->create();
        $unbookable = Wrestler::factory()->{$state}()->create(['name' => 'Unavailable Wrestler']);
        $healthy = Wrestler::factory()->bookable()->create(['name' => 'Healthy Wrestler']);
        EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$unbookable, $healthy])
            ->create();
        $marker = MatchCompetitorRouteResolver::unbookableMarker();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSeeHtml("Unavailable Wrestler</a>{$marker}")
            ->assertDontSeeHtml("Healthy Wrestler</a>{$marker}");
    })->with([
        'suspended' => 'suspended',
        'injured' => 'injured',
        'retired' => 'retired',
        'released' => 'released',
        'unemployed' => 'unemployed',
    ]);

    it('marks a deleted competitor without a link', function (): void {
        // Arrange
        $event = Event::factory()->scheduled()->create();
        $deleted = Wrestler::factory()->bookable()->create(['name' => 'Deleted Wrestler']);
        $healthy = Wrestler::factory()->bookable()->create(['name' => 'Healthy Wrestler']);
        EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$deleted, $healthy])
            ->create();
        $deleted->delete();
        $marker = MatchCompetitorRouteResolver::unbookableMarker();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component->assertSeeHtml("Deleted Wrestler{$marker}");
    });

    it('marks a tag team with an unbookable member', function (): void {
        // Arrange
        $event = Event::factory()->scheduled()->create();
        $brokenTeam = TagTeam::factory()->bookable()->create(['name' => 'Broken Team']);
        $healthyTeam = TagTeam::factory()->bookable()->create(['name' => 'Healthy Team']);
        Injury::factory()
            ->started(now())
            ->for($brokenTeam->currentWrestlers()->firstOrFail(), 'injurable')
            ->create();
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::TagTeam)
            ->withCompetitors([$brokenTeam, $healthyTeam])
            ->create();
        $marker = MatchCompetitorRouteResolver::unbookableMarker();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSeeHtml("Broken Team</a>{$marker}")
            ->assertDontSeeHtml("Healthy Team</a>{$marker}");
    });

    it('marks referees who can no longer be booked', function (): void {
        // Arrange
        $event = Event::factory()->scheduled()->create();
        $retired = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
        $healthy = Referee::factory()->bookable()->create(['first_name' => 'Healthy', 'last_name' => 'Referee']);
        $match = EventMatch::factory()->forEvent($event)->create();
        $match->referees()->attach([$retired->id, $healthy->id]);
        $marker = MatchCompetitorRouteResolver::unbookableMarker();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSeeHtml("Retired Referee</a>{$marker}")
            ->assertDontSeeHtml("Healthy Referee</a>{$marker}");
    });

    it('marks members of unresulted matches on a past event', function (): void {
        // Arrange
        $event = Event::factory()->past()->create();
        $wrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
        EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component->assertSeeHtml('Retired Wrestler</a>'.MatchCompetitorRouteResolver::unbookableMarker());
    });

    it('does not mark members of past matches that already have a result', function (): void {
        // Arrange
        $event = Event::factory()->past()->create();
        $wrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
        $referee = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
        $match = EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$wrestler])
            ->create(['match_finish' => MatchFinish::TimeLimitDraw]);
        $match->referees()->attach($referee);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSee('Retired Wrestler')
            ->assertSee('Retired Referee')
            ->assertDontSee(__('matches.no_longer_bookable'));
    });

    it('marks competitors and referees that share the same id', function (): void {
        // Arrange
        $event = Event::factory()->scheduled()->create();
        $wrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
        $referee = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
        $match = EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();
        $match->referees()->attach($referee);
        $marker = MatchCompetitorRouteResolver::unbookableMarker();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        expect($wrestler->id)->toBe($referee->id);
        $component
            ->assertSeeHtml("Retired Wrestler</a>{$marker}")
            ->assertSeeHtml("Retired Referee</a>{$marker}");
    });

    it('marks unbookable members for promotion scoped users', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->scheduled()->for($promotion, 'promotion')->create();
        $retired = Wrestler::factory()->for($promotion, 'promotion')->retired()->create(['name' => 'Retired Wrestler']);
        $healthy = Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'Healthy Wrestler']);
        EventMatch::factory()->forEvent($event)->withCompetitors([$retired, $healthy])->create();
        actingInPromotion($promotion, MembershipRole::Manager);
        $marker = MatchCompetitorRouteResolver::unbookableMarker();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSeeHtml("Retired Wrestler</a>{$marker}")
            ->assertDontSeeHtml("Healthy Wrestler</a>{$marker}");
    });

    it('checks availability in a bounded number of queries however many matches are listed', function (): void {
        // Arrange
        $event = Event::factory()->scheduled()->create();
        $addMatch = function () use ($event): void {
            $match = EventMatch::factory()
                ->forEvent($event)
                ->withCompetitors([Wrestler::factory()->retired()->create(), TagTeam::factory()->bookable()->create()])
                ->create();
            $match->referees()->attach(Referee::factory()->retired()->create());
        };
        $queriesDuring = function () use ($event): array {
            DB::flushQueryLog();
            DB::enableQueryLog();
            livewire(MatchesTable::class, ['eventId' => $event->id]);
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            return $queries;
        };
        $addMatch();
        $queriesWithOneMatch = $queriesDuring();
        collect(range(1, 4))->each($addMatch);

        // Act
        $queriesWithFiveMatches = $queriesDuring();

        // Assert
        expect($queriesWithFiveMatches)->toHaveSameSize($queriesWithOneMatch);
    });
});

describe('deleting matches', function (): void {
    it('offers removal to users who may delete matches', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $match = EventMatch::factory()->forEvent($event)->create();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSeeHtml('data-test="match-delete-action"')
            ->assertSeeHtml("wire:click=\"delete({$match->id})\"")
            ->assertSeeHtml('aria-label="Remove Match '.$match->match_number.'"')
            ->assertSeeHtml('wire:confirm="Remove match '.$match->match_number.'?"')
            ->assertSee('Remove');
    });

    it('hides removal from promotion members without delete access', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        EventMatch::factory()->forEvent($event)->create();
        actingInPromotion($promotion, MembershipRole::Member);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('data-test="match-result-action"')
            ->assertDontSeeHtml('aria-label="Match actions"')
            ->assertDontSeeHtml('data-test="match-delete-action"');
    });

    it('offers removal to promotion managers', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        EventMatch::factory()->forEvent($event)->create();
        actingInPromotion($promotion, MembershipRole::Manager);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('data-test="match-delete-action"');
    });

    it('soft deletes a match and dispatches success feedback', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $match = EventMatch::factory()->forEvent($event)->create();
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Act
        $component->call('delete', $match);

        // Assert
        $component
            ->assertHasNoErrors()
            ->assertDispatched('flash-message', type: 'status', message: __('matches.actions.deleted'))
            ->assertDontSeeHtml("wire:click=\"delete({$match->id})\"");

        $transition = $match->lifecycleTransitions()->sole();

        expect($match->refresh()->trashed())->toBeTrue()
            ->and($transition->dimension)->toBe(LifecycleDimension::Deletion)
            ->and($transition->transition)->toBe(LifecycleTransitionType::Deleted);
    });

    it('recomputes the remembered match total after a match is deleted', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $matches = EventMatch::factory()->count(2)->forEvent($event)->create();
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Act
        $component->call('delete', $matches->first());

        // Assert
        expect($component->get('metadataSnapshot')['total'])->toBe(1);
    });

    it('forbids members without delete access from deleting a match', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        $match = EventMatch::factory()->forEvent($event)->create();
        actingInPromotion($promotion, MembershipRole::Member);
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Act
        $component->call('delete', $match);

        // Assert
        $component->assertForbidden();

        expect($match->refresh()->trashed())->toBeFalse();
    });
});

describe('search and event scoping', function (): void {
    it('searches matches by type and clears the search', function (string $searchTerm): void {
        // Arrange
        $event = Event::factory()->create();
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::Singles)
            ->create();
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::TagTeam)
            ->create();
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Act
        $component->set('search', $searchTerm);

        // Assert
        $component
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('Singles')
            ->assertSee('Tag Team');
    })->with([
        'capitalized' => ['Singles'],
        'lowercase' => ['singles'],
        'uppercase' => ['SINGLES'],
    ]);

    it('renders only matches belonging to the selected event', function (): void {
        // Arrange
        $selectedEvent = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        EventMatch::factory()
            ->forEvent($selectedEvent)
            ->withMatchType(MatchType::Singles)
            ->create();
        EventMatch::factory()
            ->forEvent($otherEvent)
            ->withMatchType(MatchType::TagTeam)
            ->create();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $selectedEvent->id]);

        // Assert
        $component
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');
    });
});

it('paginates rendered matches using the selected page size', function (): void {
    // Arrange
    $event = Event::factory()->create();
    foreach (
        [
            MatchType::Singles,
            MatchType::TagTeam,
            MatchType::TripleThreat,
            MatchType::Triangle,
            MatchType::Fatal4Way,
            MatchType::BattleRoyal,
        ] as $index => $matchType
    ) {
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType($matchType)
            ->withMatchNumber($index + 1)
            ->create();
    }
    $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

    // Act
    $component->set('perPage', 5);

    // Assert
    $component
        ->assertSee('Singles')
        ->assertSee('Tag Team')
        ->assertSee('Triple Threat')
        ->assertSee('Triangle')
        ->assertSee('Fatal 4 Way')
        ->assertDontSee('Battle Royal');

    // Act
    $component->call('nextPage');

    // Assert
    $component
        ->assertSee('Battle Royal')
        ->assertDontSee('Singles');
});

it('lists matches in card order whatever order they were created in', function (): void {
    // Arrange
    $event = Event::factory()->create();
    EventMatch::factory()->forEvent($event)->withMatchType(MatchType::BattleRoyal)->create(['match_number' => 3]);
    EventMatch::factory()->forEvent($event)->withMatchType(MatchType::Singles)->create(['match_number' => 1]);
    EventMatch::factory()->forEvent($event)->withMatchType(MatchType::TagTeam)->create(['match_number' => 2]);

    // Act
    $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

    // Assert
    $component->assertSeeInOrder(['Singles', 'Tag Team', 'Battle Royal']);
});

it('lists a match\'s referees, titles and teammates in id order whatever order they were added in', function (): void {
    // Arrange
    $event = Event::factory()->create();
    [$firstReferee, $secondReferee] = Referee::factory()->count(2)->sequence(['last_name' => 'Firstref'], ['last_name' => 'Secondref'])->create()->all();
    [$firstTitle, $secondTitle] = Title::factory()->count(2)->tagTeam()->sequence(['name' => 'First Belt'], ['name' => 'Second Belt'])->create()->all();
    [$firstWrestler, $secondWrestler] = Wrestler::factory()->count(2)->sequence(['name' => 'First Partner'], ['name' => 'Second Partner'])->create()->all();
    $match = EventMatch::factory()->forEvent($event)->withMatchType(MatchType::TagTeam)->create();
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($secondWrestler, 'competitor')->create(['id' => 9002]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($firstWrestler, 'competitor')->create(['id' => 9001]);
    $match->referees()->attach($secondReferee);
    $match->referees()->attach($firstReferee);
    $match->titles()->attach($secondTitle);
    $match->titles()->attach($firstTitle);

    // Act
    $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

    // Assert
    $component
        ->assertSeeInOrder(['First Partner', 'Second Partner'])
        ->assertSeeInOrder(['Firstref', 'Secondref'])
        ->assertSeeInOrder(['First Belt', 'Second Belt']);
});

it('forbids users without administrative access', function (string $actor, int $status): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    $event = Event::factory()->create();

    // Act
    $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

    // Assert
    $component->assertStatus($status);
})->with([
    'guest' => ['guest', 403],
    'basic user' => ['basic user', 404],
]);
