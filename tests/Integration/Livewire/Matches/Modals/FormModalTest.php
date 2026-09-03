<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    actingAs($this->admin);
    $this->event = Event::factory()->create();
});

describe('FormModal Configuration', function () {
    it('initializes the match form', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id]);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
    });

    it('returns correct model class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(EventMatch::class);
    });
});

describe('FormModal Rendering', function () {
    it('can render in create mode', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $match = EventMatch::factory()->for($this->event)->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertSee('Create Match');
    });

    it('displays correct title in edit mode', function () {
        $match = EventMatch::factory()->for($this->event)->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id);

        $component->assertSee('Edit Match');
    });

    it('presents wrestlers list for selection', function () {
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Test Wrestler']);

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles);

        $component->assertSee('Test Wrestler');
    });

    it('presents referees list for selection', function () {
        $referee = Referee::factory()->bookable()->create(['first_name' => 'Test', 'last_name' => 'Referee']);

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertSee('Test Referee');
    });

    it('presents match types list for selection', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        // Match types are now enums, so they should all be listed
        $component->assertSee('Singles');
    });

    it('renders controls for every flexible competitor side', function (MatchType $matchType) {
        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', $matchType)
            ->assertSeeHtml('wire:model="form.competitors.0.wrestlers"')
            ->assertSeeHtml('wire:model="form.competitors.1.wrestlers"')
            ->assertSeeHtml('wire:model="form.competitors.0.tag_teams"')
            ->assertSeeHtml('wire:model="form.competitors.1.tag_teams"');
    })->with([
        MatchType::TwoOnOneHandicap,
        MatchType::ThreeOnTwoHandicap,
        MatchType::Gauntlet,
    ]);

    it('fills the form with valid match data', function () {
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $unavailableWrestler = Wrestler::factory()->create();
        $unavailableReferee = Referee::factory()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->call('fillDummyFields')
            ->assertSet('form.matchType', MatchType::Singles)
            ->assertSet('form.competitors', function (array $competitors) use ($wrestlers): bool {
                $competitorIds = collect($competitors)
                    ->pluck('wrestlers')
                    ->flatten()
                    ->sort()
                    ->values()
                    ->all();

                return $competitorIds === $wrestlers->modelKeys();
            })
            ->assertSet('form.referees', [$referee->id])
            ->assertSet('form.competitors', function (array $competitors) use ($unavailableWrestler): bool {
                return ! collect($competitors)
                    ->pluck('wrestlers')
                    ->flatten()
                    ->contains($unavailableWrestler->id);
            })
            ->assertSet('form.referees', fn (array $refereeIds): bool => ! in_array(
                $unavailableReferee->id,
                $refereeIds,
                true,
            ));
    });
});

describe('FormModal Match Stipulation Integration', function () {
    it('presents only active stipulations', function () {
        $activeStipulation = MatchStipulation::factory()->active()->create(['name' => 'Steel Cage']);
        $inactiveStipulation = MatchStipulation::factory()->inactive()->create(['name' => 'Retired Rules']);

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->assertSee($activeStipulation->name)
            ->assertDontSee($inactiveStipulation->name);
    });

    it('persists an active stipulation when creating a match', function () {
        $stipulation = MatchStipulation::factory()->active()->create();
        $firstWrestler = Wrestler::factory()->bookable()->create();
        $secondWrestler = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.matchStipulationId', $stipulation->id)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors', [
                ['wrestlers' => [$firstWrestler->id]],
                ['wrestlers' => [$secondWrestler->id]],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();

        expect($match->matchStipulation()->sole()->is($stipulation))->toBeTrue();
    });

    it('rejects inactive stipulations', function () {
        $stipulation = MatchStipulation::factory()->inactive()->create();
        $firstWrestler = Wrestler::factory()->bookable()->create();
        $secondWrestler = Wrestler::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.matchStipulationId', $stipulation->id)
            ->set('form.competitors', [
                ['wrestlers' => [$firstWrestler->id]],
                ['wrestlers' => [$secondWrestler->id]],
            ])
            ->call('save')
            ->assertHasErrors(['form.matchStipulationId']);
    });

    it('loads the existing stipulation when editing a match', function () {
        $stipulation = MatchStipulation::factory()->active()->create();
        $match = EventMatch::factory()->for($this->event)->create([
            'match_stipulation_id' => $stipulation->id,
        ]);

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id)
            ->assertSet('form.matchStipulationId', $stipulation->id);
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new match with valid data', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                0 => ['wrestlers' => [$wrestler1->id]],
                1 => ['wrestlers' => [$wrestler2->id]],
            ])
            ->set('form.referees', [$referee->id])
            ->set('form.preview', 'Epic wrestling match preview')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchCreated');

        $this->assertDatabaseHas('events_matches', [
            'event_id' => $this->event->id,
            'match_type' => MatchType::Singles->value,
            'preview' => 'Epic wrestling match preview',
        ]);

        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();
        expect($match->sides()->pluck('position')->all())->toBe([1, 2]);
    });

    it('validates required fields when creating', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', '')
            ->call('save');

        $component->assertHasErrors([
            'form.matchType' => 'required',
        ]);
    });

    it('validates minimum number of competitors', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [$wrestler->id])
            ->call('save');

        $component->assertHasErrors(['form.competitors']);
    });

    it('requires a competitor on every flexible match side', function (MatchType $matchType) {
        $wrestler = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', $matchType)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors.0.wrestlers', [$wrestler->id])
            ->call('save')
            ->assertHasErrors([
                'form.competitors.1.wrestlers' => 'required_without',
            ]);
    })->with([
        MatchType::TwoOnOneHandicap,
        MatchType::ThreeOnTwoHandicap,
        MatchType::Gauntlet,
    ]);

    it('translates invalid match composition into form feedback', function (MatchType $matchType, string $message) {
        $wrestlers = Wrestler::factory()->count(4)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', $matchType)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors', [
                ['wrestlers' => $wrestlers->take(2)->modelKeys()],
                ['wrestlers' => $wrestlers->skip(2)->modelKeys()],
            ])
            ->call('save')
            ->assertHasErrors(['form.configuration'])
            ->assertSee($message);

        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    })->with([
        [MatchType::SixManTagTeam, 'The [6 Man Tag Team] match requires a 3-on-3 roster-member composition.'],
        [MatchType::EightManTagTeam, 'The [8 Man Tag Team] match requires a 4-on-4 roster-member composition.'],
        [MatchType::TenManTagTeam, 'The [10 Man Tag Team] match requires a 5-on-5 roster-member composition.'],
    ]);

    it('validates match type exists', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        expect(fn () => livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', 'invalid-match-type'))
            ->toThrow(ValueError::class);
    });

    it('validates competitors exist and are bookable', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                0 => ['wrestlers' => [999]],
                1 => ['wrestlers' => [998]],
            ])
            ->call('save');

        $component->assertHasErrors(['form.competitors.0.wrestlers.0', 'form.competitors.1.wrestlers.0']);
    });

    it('rejects an unavailable wrestler before constructing match data', function () {
        $unavailableWrestler = Wrestler::factory()->retired()->create();
        $availableWrestler = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                ['wrestlers' => [$unavailableWrestler->id]],
                ['wrestlers' => [$availableWrestler->id]],
            ])
            ->set('form.referees', [$referee->id])
            ->call('save')
            ->assertHasErrors(['form.competitors.0.wrestlers.0']);
    });

    it('validates referees exist and are bookable', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                0 => ['wrestlers' => [$wrestler1->id]],
                1 => ['wrestlers' => [$wrestler2->id]],
            ])
            ->set('form.referees', [999])
            ->call('save');

        $component->assertHasErrors(['form.referees.0']);
    });

    it('rejects an unavailable referee before constructing match data', function () {
        $firstWrestler = Wrestler::factory()->bookable()->create();
        $secondWrestler = Wrestler::factory()->bookable()->create();
        $unavailableReferee = Referee::factory()->retired()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                ['wrestlers' => [$firstWrestler->id]],
                ['wrestlers' => [$secondWrestler->id]],
            ])
            ->set('form.referees', [$unavailableReferee->id])
            ->call('save')
            ->assertHasErrors(['form.referees.0']);
    });

    it('rejects an unavailable tag team before constructing match data', function () {
        $unavailableTagTeam = TagTeam::factory()->retired()->create();
        $availableTagTeam = TagTeam::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::TagTeam)
            ->set('form.competitors', [
                ['tag_teams' => [$unavailableTagTeam->id]],
                ['tag_teams' => [$availableTagTeam->id]],
            ])
            ->set('form.referees', [$referee->id])
            ->call('save')
            ->assertHasErrors(['form.competitors.0.tag_teams.0']);
    });

    it('persists each battle royal entrant on an individual side', function () {
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::BattleRoyal)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors.0.wrestlers', $wrestlers->modelKeys())
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('matchCreated');

        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();

        expect($match->sides()->pluck('position')->all())->toBe([1, 2, 3])
            ->and($match->competitors)->toHaveCount(3)
            ->and($match->competitors->pluck('match_side_id')->unique())->toHaveCount(3);
    });

    it('records royal rumble selection order as entrant order', function () {
        $wrestlers = Wrestler::factory()->count(10)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::RoyalRumble)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors.0.wrestlers', $wrestlers->modelKeys())
            ->call('save')
            ->assertHasNoErrors();

        $entryOrder = EventMatch::query()
            ->whereBelongsTo($this->event)
            ->sole()
            ->competitors()
            ->orderBy('entry_order')
            ->pluck('entry_order')
            ->all();

        expect($entryOrder)->toBe(range(1, 10));
    });

    it('requires the configured minimum number of individual entrants', function (MatchType $matchType, int $entrantCount) {
        $wrestlers = Wrestler::factory()->count($entrantCount)->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', $matchType)
            ->set('form.competitors.0.wrestlers', $wrestlers->modelKeys())
            ->call('save')
            ->assertHasErrors(['form.competitors.0.wrestlers' => 'min']);
    })->with([
        'battle royal' => [MatchType::BattleRoyal, 2],
        'royal rumble' => [MatchType::RoyalRumble, 9],
    ]);
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing match', function () {
        $match = EventMatch::factory()->for($this->event)->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $wrestler3 = Wrestler::factory()->bookable()->create();
        $wrestler4 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id)
            ->set('form.matchType', MatchType::TagTeam)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors', [
                0 => ['wrestlers' => [$wrestler1->id, $wrestler2->id]],
                1 => ['wrestlers' => [$wrestler3->id, $wrestler4->id]],
            ])
            ->set('form.preview', 'Updated match preview')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchUpdated');

        $this->assertDatabaseHas('events_matches', [
            'id' => $match->id,
            'match_type' => MatchType::TagTeam->value,
            'preview' => 'Updated match preview',
        ]);
    });

    it('loads existing match data in edit mode', function () {
        $match = EventMatch::factory()
            ->for($this->event)
            ->state(['match_type' => MatchType::Singles, 'preview' => 'Original preview'])
            ->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id);

        $component->assertSet('eventId', $this->event->id);
        $component->assertSet('form.matchType', MatchType::Singles);
        $component->assertSet('form.preview', 'Original preview');
    });

    it('loads existing competitors grouped by their ordered sides', function () {
        $match = EventMatch::factory()
            ->for($this->event)
            ->state(['match_type' => MatchType::TagTeam])
            ->create();
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->bookable()->create();
        $firstSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
        $secondSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
        MatchCompetitor::factory()->create([
            'match_id' => $match->id,
            'match_side_id' => $firstSide->id,
            'competitor_type' => $wrestler->getMorphClass(),
            'competitor_id' => $wrestler->id,
        ]);
        MatchCompetitor::factory()->create([
            'match_id' => $match->id,
            'match_side_id' => $secondSide->id,
            'competitor_type' => $tagTeam->getMorphClass(),
            'competitor_id' => $tagTeam->id,
        ]);

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id)
            ->assertSet('form.competitors', [
                ['wrestlers' => [$wrestler->id], 'tag_teams' => []],
                ['wrestlers' => [], 'tag_teams' => [$tagTeam->id]],
            ]);
    });

    it('loads individual entrant sides into one selection bucket', function () {
        $match = EventMatch::factory()
            ->for($this->event)
            ->battleRoyal(3)
            ->create();
        $wrestlerIds = $match->competitors()
            ->with('side')
            ->get()
            ->sortBy('side.position')
            ->pluck('competitor_id')
            ->values()
            ->all();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id)
            ->assertSet('form.competitors', [
                ['wrestlers' => $wrestlerIds, 'tag_teams' => []],
            ]);
    });

    it('rejects a competitor selected on multiple sides', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                ['wrestlers' => [$wrestler->id]],
                ['wrestlers' => [$wrestler->id]],
            ])
            ->call('save')
            ->assertHasErrors([
                'form.competitors.0.wrestlers.0' => 'distinct',
                'form.competitors.1.wrestlers.0' => 'distinct',
            ]);
    });
});

describe('FormModal Title Championship Integration', function () {
    it('can create championship match with title stakes', function () {
        $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors', [
                0 => ['wrestlers' => [$wrestler1->id]],
                1 => ['wrestlers' => [$wrestler2->id]],
            ])
            ->set('form.titles', [$title->id])
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchCreated');

        $match = EventMatch::where('event_id', $this->event->id)->firstOrFail();
        expect($match->titles->pluck('id'))->toContain($title->id);
    });

    it('presents available titles for championship matches', function () {
        $title = Title::factory()->active()->create(['name' => 'World Championship']);

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertSee('World Championship');
    });

    it('validates title stakes are active titles', function () {
        $inactiveTitle = Title::factory()->inactive()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.competitors', [
                0 => ['wrestlers' => [$wrestler1->id]],
                1 => ['wrestlers' => [$wrestler2->id]],
            ])
            ->set('form.titles', [$inactiveTitle->id])
            ->call('save');

        $component->assertHasErrors(['form.titles.0']);
    });
});

describe('FormModal Tag Team Integration', function () {
    it('can create tag team match', function () {
        $tagTeam1 = TagTeam::factory()->bookable()->create();
        $tagTeam2 = TagTeam::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::TagTeam)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors', [
                0 => ['tag_teams' => [$tagTeam1->id]],
                1 => ['tag_teams' => [$tagTeam2->id]],
            ])
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchCreated');
    });

    it('presents available tag teams for selection', function () {
        $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'The Hardy Boyz']);

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::TagTeam);

        $component->assertSee('The Hardy Boyz');
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $match = EventMatch::factory()->for($this->event)->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal', $match->id)
            ->call('openModal');

        $component->assertSet('eventId', $this->event->id);
        $component->assertSet('form.matchType', null);
        $component->assertSet('form.competitors', []);
    });

    it('closes modal after successful save', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.referees', [$referee->id])
            ->set('form.competitors', [
                0 => ['wrestlers' => [$wrestler1->id]],
                1 => ['wrestlers' => [$wrestler2->id]],
            ])
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertForbidden();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        actingAs($user);

        $component = livewire(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertForbidden();
    });
});
