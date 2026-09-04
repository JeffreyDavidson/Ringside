<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized match form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
        $this->event = Event::factory()->create();
    });

    it('renders match fields and available choices', function () {
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        $referee = Referee::factory()->bookable()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $title = Title::factory()->active()->singles()->create(['name' => 'World Heavyweight Title']);
        $activeStipulation = MatchStipulation::factory()->active()->create(['name' => 'Steel Cage']);
        $inactiveStipulation = MatchStipulation::factory()->inactive()->create(['name' => 'Retired Rules']);

        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->set('form.matchType', MatchType::Singles);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.matches.modals.form-modal');
        $modal
            ->assertPropertyWired('form.matchType')
            ->assertPropertyWired('form.matchStipulationId')
            ->assertPropertyWired('form.referees')
            ->assertPropertyWired('form.titles')
            ->assertPropertyWired('form.preview')
            ->assertSee($wrestler->name)
            ->assertSee($referee->full_name)
            ->assertSee($title->name)
            ->assertSee($activeStipulation->name)
            ->assertDontSee($inactiveStipulation->name);
    });

    it('opens a blank form and configures sides for the selected match type', function () {
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.competitors', [
                ['wrestlers' => [], 'tag_teams' => []],
                ['wrestlers' => [], 'tag_teams' => []],
            ])
            ->assertSee('Create Match')
            ->assertPropertyWired('form.competitors.0.wrestlers.0')
            ->assertPropertyWired('form.competitors.1.wrestlers.0');
    });

    it('loads an existing match configuration for editing', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $title = Title::factory()->active()->tagTeam()->create();
        $stipulation = MatchStipulation::factory()->active()->create();
        $match = EventMatch::factory()
            ->for($this->event)
            ->withCompetitors([$wrestler, $tagTeam])
            ->create([
                'match_type' => MatchType::TagTeam,
                'match_stipulation_id' => $stipulation->id,
                'preview' => 'Original preview.',
            ]);
        $match->referees()->attach($referee);
        $match->titles()->attach($title);
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal', $match->id);
        $modal->set('form.preview', 'Original preview.');

        $modal
            ->assertSet('form.matchType', MatchType::TagTeam)
            ->assertSet('form.matchStipulationId', $stipulation->id)
            ->assertSet('form.referees', [$referee->id])
            ->assertSet('form.titles', [$title->id])
            ->assertSet('form.competitors', [
                ['wrestlers' => [$wrestler->id], 'tag_teams' => []],
                ['wrestlers' => [], 'tag_teams' => [$tagTeam->id]],
            ])
            ->assertSet('form.preview', 'Original preview.')
            ->assertSee('Edit Match');
    });

    it('propagates a missing match failure', function () {
        expect(fn () => livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates a singles match with its complete configuration', function () {
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $wrestlerIds = $wrestlers->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $title = Title::factory()->active()->singles()->create();
        $stipulation = MatchStipulation::factory()->active()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.matchStipulationId' => $stipulation->id,
            'form.competitors' => [
                ['wrestlers' => [$wrestlerIds[0]], 'tag_teams' => []],
                ['wrestlers' => [$wrestlerIds[1]], 'tag_teams' => []],
            ],
            'form.referees' => [$referee->id],
            'form.titles' => [$title->id],
            'form.preview' => 'Championship match preview.',
        ]);
        $modal->call('save');

        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();
        expect($match->match_type)->toBe(MatchType::Singles)
            ->and($match->match_stipulation_id)->toBe($stipulation->id)
            ->and($match->preview)->toBe('Championship match preview.')
            ->and($match->sides()->pluck('position')->all())->toBe([1, 2])
            ->and($match->wrestlers()->pluck('wrestlers.id')->sort()->values()->all())
            ->toBe($wrestlers->modelKeys())
            ->and($match->referees()->pluck('referees.id')->all())->toBe([$referee->id])
            ->and($match->titles()->pluck('titles.id')->all())->toBe([$title->id]);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('matchCreated')
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false)
            ->assertSet('form.matchType', null);
    });

    it('creates a match between tag teams', function () {
        $tagTeams = TagTeam::factory()->count(2)->bookable()->create();
        $tagTeamIds = $tagTeams->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::TagTeam);
        $modal->set([
            'form.competitors' => [
                ['tag_teams' => [$tagTeamIds[0]]],
                ['tag_teams' => [$tagTeamIds[1]]],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();
        expect($match->match_type)->toBe(MatchType::TagTeam)
            ->and($match->tagTeams()->pluck('tag_teams.id')->sort()->values()->all())
            ->toBe($tagTeams->modelKeys())
            ->and($match->sides)->toHaveCount(2);
        $modal->assertHasNoErrors()->assertDispatched('matchCreated');
    });

    it('persists each individual entrant on an ordered side', function (MatchType $matchType, int $entrantCount, array $entryOrder) {
        $wrestlers = Wrestler::factory()->count($entrantCount)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', $matchType);
        $modal->set('form.competitors.0.wrestlers', $wrestlers->modelKeys());
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();
        expect($match->sides()->pluck('position')->all())->toBe(range(1, $entrantCount))
            ->and($match->competitors()->orderBy('entry_order')->pluck('entry_order')->all())
            ->toBe($entryOrder);
        $modal->assertHasNoErrors();
    })->with([
        'battle royal' => [MatchType::BattleRoyal, 3, [null, null, null]],
        'royal rumble' => [MatchType::RoyalRumble, 10, range(1, 10)],
    ]);

    it('requires a match type, competitors, and a referee', function () {
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->call('save');

        $modal
            ->assertHasErrors([
                'form.matchType' => 'required',
                'form.referees' => 'required',
            ])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('rejects unavailable wrestlers and referees', function () {
        $unavailableWrestler = Wrestler::factory()->retired()->create();
        $availableWrestler = Wrestler::factory()->bookable()->create();
        $unavailableReferee = Referee::factory()->retired()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$unavailableWrestler->id], 'tag_teams' => []],
                ['wrestlers' => [$availableWrestler->id], 'tag_teams' => []],
            ],
            'form.referees' => [$unavailableReferee->id],
        ]);
        $modal->call('save');

        $modal->assertHasErrors([
            'form.competitors.0.wrestlers.0',
            'form.referees.0',
        ]);
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('rejects an unavailable tag team', function () {
        $unavailableTagTeam = TagTeam::factory()->retired()->create();
        $availableTagTeam = TagTeam::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::TagTeam);
        $modal->set([
            'form.competitors' => [
                ['tag_teams' => [$unavailableTagTeam->id]],
                ['tag_teams' => [$availableTagTeam->id]],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        $modal->assertHasErrors(['form.competitors.0.tag_teams.0']);
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('rejects inactive stipulations and titles', function () {
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $wrestlerIds = $wrestlers->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $inactiveStipulation = MatchStipulation::factory()->inactive()->create();
        $inactiveTitle = Title::factory()->inactive()->singles()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.matchStipulationId' => $inactiveStipulation->id,
            'form.competitors' => [
                ['wrestlers' => [$wrestlerIds[0]], 'tag_teams' => []],
                ['wrestlers' => [$wrestlerIds[1]], 'tag_teams' => []],
            ],
            'form.referees' => [$referee->id],
            'form.titles' => [$inactiveTitle->id],
        ]);
        $modal->call('save');

        $modal->assertHasErrors([
            'form.matchStipulationId' => 'exists',
            'form.titles.0',
        ]);
    });

    it('rejects a competitor selected on multiple sides', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$wrestler->id], 'tag_teams' => []],
                ['wrestlers' => [$wrestler->id], 'tag_teams' => []],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        $modal->assertHasErrors([
            'form.competitors.0.wrestlers.0' => 'distinct',
            'form.competitors.1.wrestlers.0' => 'distinct',
        ]);
    });

    it('translates invalid match composition into form feedback', function () {
        $wrestlers = Wrestler::factory()->count(4)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::SixManTagTeam);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => $wrestlers->take(2)->modelKeys()],
                ['wrestlers' => $wrestlers->skip(2)->modelKeys()],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.configuration'])
            ->assertSee('The [6 Man Tag Team] match requires a 3-on-3 roster-member composition.')
            ->assertNotDispatched('closeModal');
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('requires the minimum number of individual entrants', function (MatchType $matchType, int $entrantCount) {
        $wrestlers = Wrestler::factory()->count($entrantCount)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->set('form.matchType', $matchType);
        $modal->set('form.competitors.0.wrestlers', $wrestlers->modelKeys());
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        $modal->assertHasErrors(['form.competitors.0.wrestlers' => 'min']);
    })->with([
        'battle royal' => [MatchType::BattleRoyal, 2],
        'royal rumble' => [MatchType::RoyalRumble, 9],
    ]);

    it('updates an existing match configuration', function () {
        $oldWrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $oldReferee = Referee::factory()->bookable()->create();
        $match = EventMatch::factory()
            ->for($this->event)
            ->withCompetitors($oldWrestlers->all())
            ->create(['match_type' => MatchType::Singles]);
        $match->referees()->attach($oldReferee);
        $newWrestlers = Wrestler::factory()->count(4)->bookable()->create();
        $newReferee = Referee::factory()->bookable()->create();
        $newStipulation = MatchStipulation::factory()->active()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal', $match->id);
        $modal->set('form.matchType', MatchType::TagTeam);
        $modal->set([
            'form.matchStipulationId' => $newStipulation->id,
            'form.competitors' => [
                ['wrestlers' => $newWrestlers->take(2)->modelKeys()],
                ['wrestlers' => $newWrestlers->skip(2)->modelKeys()],
            ],
            'form.referees' => [$newReferee->id],
            'form.preview' => 'Updated preview.',
        ]);
        $modal->call('save');

        $match->refresh();
        expect($match->match_type)->toBe(MatchType::TagTeam)
            ->and($match->match_stipulation_id)->toBe($newStipulation->id)
            ->and($match->preview)->toBe('Updated preview.')
            ->and($match->wrestlers()->pluck('wrestlers.id')->sort()->values()->all())
            ->toBe($newWrestlers->modelKeys())
            ->and($match->referees()->pluck('referees.id')->all())->toBe([$newReferee->id]);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('matchUpdated')
            ->assertSet('isModalOpen', false);
    });

    it('resets an edited match when reopening in create mode', function () {
        $match = EventMatch::factory()->for($this->event)->create([
            'match_type' => MatchType::Singles,
            'preview' => 'Unsaved edit.',
        ]);
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal', $match->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.matchType', null)
            ->assertSet('form.matchStipulationId', null)
            ->assertSet('form.competitors', [])
            ->assertSet('form.referees', [])
            ->assertSet('form.titles', [])
            ->assertSet('form.preview', '');
    });

    it('generates valid dummy data that can create a match', function () {
        Wrestler::factory()->count(2)->bookable()->create();
        Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('matchCreated')
            ->assertSet('isModalOpen', false);
        expect(EventMatch::query()->whereBelongsTo($this->event)->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the match form', function (string $actor, string $operation) {
    $event = Event::factory()->create();
    $match = $operation === 'update'
        ? EventMatch::factory()->for($event)->create()
        : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class, ['eventId' => $event->id]);
    $modal->call('openModal', $match?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
