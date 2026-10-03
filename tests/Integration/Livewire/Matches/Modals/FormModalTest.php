<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Livewire\Matches\Modals\FormModal;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionContextService;
use JMac\Testing\Double;
use Livewire\Features\SupportTesting\Testable;
use LivewireUI\Modal\Modal;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized match form interactions', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
        $this->event = Event::factory()->create();
    });

    it('passes event context through the modal dispatcher', function (): void {
        // Arrange
        $component = 'matches.modals.form-modal';
        $arguments = ['eventId' => $this->event->id];

        // Act
        $modal = livewire(Modal::class)->dispatch(
            'openModal',
            component: $component,
            arguments: $arguments,
        );

        // Assert
        $modal
            ->assertCount('components', 1)
            ->assertSet('components', function (array $components) use ($component, $arguments): bool {
                $registeredComponent = array_first($components) ?? null;

                return $registeredComponent['name'] === $component
                    && $registeredComponent['arguments'] === $arguments;
            })
            ->assertNotSet('activeComponent', null);
    });

    it('renders match fields and available choices', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        $referee = Referee::factory()->bookable()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $title = Title::factory()->active()->singles()->create(['name' => 'World Heavyweight Title']);
        $activeStipulation = MatchStipulation::factory()->active()->create(['name' => 'Steel Cage']);
        $inactiveStipulation = MatchStipulation::factory()->inactive()->create(['name' => 'Retired Rules']);

        // Act
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->set('form.matchType', MatchType::Singles);

        // Assert
        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.matches.modals.form-modal');
        $modal
            ->assertPropertyWired('form.matchType')
            ->assertPropertyWired('form.matchStipulationId')
            ->assertPropertyWired('form.titles')
            ->assertPropertyWired('form.preview')
            ->assertSeeHtml('data-field="form.competitors.0.wrestlers.0"')
            ->assertSeeHtml('data-field="form.referees"')
            ->assertDontSee($wrestler->name)
            ->assertDontSee($referee->full_name)
            ->assertSee($title->name)
            ->assertSee($activeStipulation->name)
            ->assertDontSee($inactiveStipulation->name);
    });

    it('opens a blank form and configures sides for the selected match type', function (): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);

        // Assert
        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.competitors', [
                ['wrestlers' => [], 'tag_teams' => []],
                ['wrestlers' => [], 'tag_teams' => []],
            ])
            ->assertSee('Add Match')
            ->assertSeeHtml('data-field="form.competitors.0.wrestlers.0"')
            ->assertSeeHtml('data-field="form.competitors.1.wrestlers.0"');
    });

    it('prompts for a match type instead of implying one is selected', function (): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');

        // Assert
        $modal
            ->assertSet('form.matchType', null)
            ->assertSeeHtml('<option value="">Select a match type</option>');
    });

    it('returns to the match type prompt when the selection is cleared', function (): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set('form.matchType', '');

        // Assert
        $modal
            ->assertSet('form.matchType', null)
            ->assertSee('Select a match type to configure competitors');
    });

    it('loads an existing match configuration for editing', function (): void {
        // Arrange
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

        // Act
        $modal->call('openModal', $match->id);
        $modal->set('form.preview', 'Original preview.');

        // Assert
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

    it('responds not found when opening a missing match', function (): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal', PHP_INT_MAX);

        // Assert
        $modal->assertNotFound();
    });

    it('creates a singles match with its complete configuration', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $wrestlerIds = $wrestlers->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $title = Title::factory()->active()->singles()->create();
        $stipulation = MatchStipulation::factory()->active()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
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

    it('creates a match between tag teams', function (): void {
        // Arrange
        $tagTeams = TagTeam::factory()->count(2)->bookable()->create();
        $tagTeamIds = $tagTeams->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();
        expect($match->match_type)->toBe(MatchType::TagTeam)
            ->and($match->tagTeams()->pluck('tag_teams.id')->sort()->values()->all())
            ->toBe($tagTeams->modelKeys())
            ->and($match->sides)->toHaveCount(2);
        $modal->assertHasNoErrors()->assertDispatched('matchCreated');
    });

    it('persists each individual entrant on an ordered side', function (MatchType $matchType, int $entrantCount, array $entryOrder): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count($entrantCount)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', $matchType);
        $modal->set('form.competitors.0.wrestlers', $wrestlers->modelKeys());
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        // Assert
        $match = EventMatch::query()->whereBelongsTo($this->event)->sole();
        expect($match->sides()->pluck('position')->all())->toBe(range(1, $entrantCount))
            ->and($match->competitors()->orderBy('entry_order')->pluck('entry_order')->all())
            ->toBe($entryOrder);
        $modal->assertHasNoErrors();
    })->with([
        'battle royal' => [MatchType::BattleRoyal, 3, [null, null, null]],
        'royal rumble' => [MatchType::RoyalRumble, 10, range(1, 10)],
    ]);

    it('requires a match type, competitors, and a referee', function (): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors([
                'form.matchType' => 'required',
                'form.referees' => 'required',
            ])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('uses the friendly match stipulation name in validation messages', function (): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->call('openModal');
        $modal->set('form.matchStipulationId', PHP_INT_MAX);

        // Act
        $modal->call('save');

        // Assert
        expect($modal->instance()->getErrorBag()->first('form.matchStipulationId'))
            ->toBe('The selected match stipulation is invalid.');
    });

    it('attaches a current champion failure found while saving to the titles field', function (): void {
        // Arrange
        $wrestlerIds = Wrestler::factory()->count(2)->bookable()->create()->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $title = Title::factory()->active()->singles()->create(['name' => 'World Heavyweight Title']);
        $action = Double::for(AddMatchForEventAction::class);
        $action->expects('handle')->throws(InvalidMatchConfigurationException::currentChampionMissing($title));
        app()->instance(AddMatchForEventAction::class, $action);
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$wrestlerIds[0]], 'tag_teams' => []],
                ['wrestlers' => [$wrestlerIds[1]], 'tag_teams' => []],
            ],
            'form.referees' => [$referee->id],
            'form.titles' => [$title->id],
        ]);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.titles'])
            ->assertHasNoErrors(['form.configuration'])
            ->assertNotDispatched('matchCreated')
            ->assertSet('isModalOpen', true);
        expect($modal->instance()->getErrorBag()->first('form.titles'))
            ->toBe('The current champion of [World Heavyweight Title] must compete in the title match.');
        $action->verify();
    });

    it('shows a scheduling conflict found while saving on the configuration field', function (): void {
        // Arrange
        $wrestlerIds = Wrestler::factory()->count(2)->bookable()->create()->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $action = Double::for(AddMatchForEventAction::class);
        $action->expects('handle')->throws(SchedulingConflictException::competitorAlreadyBooked('Wrestler', 'John Cena'));
        app()->instance(AddMatchForEventAction::class, $action);
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$wrestlerIds[0]], 'tag_teams' => []],
                ['wrestlers' => [$wrestlerIds[1]], 'tag_teams' => []],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.configuration'])
            ->assertHasNoErrors(['form.titles'])
            ->assertNotDispatched('matchCreated')
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true)
            ->assertSet('form.referees', [$referee->id]);
        expect($modal->instance()->getErrorBag()->first('form.configuration'))
            ->toBe('Wrestler [John Cena] is already booked at this event time.')
            ->and(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
        $action->verify();
    });

    it('shows an unavailable entity found while saving on the configuration field', function (): void {
        // Arrange
        $wrestlerIds = Wrestler::factory()->count(2)->bookable()->create()->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $action = Double::for(AddMatchForEventAction::class);
        $action->expects('handle')->throws(EntityNotAvailableException::forMatchAssignment('wrestlers'));
        app()->instance(AddMatchForEventAction::class, $action);
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$wrestlerIds[0]], 'tag_teams' => []],
                ['wrestlers' => [$wrestlerIds[1]], 'tag_teams' => []],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.configuration'])
            ->assertNotDispatched('matchCreated')
            ->assertSet('isModalOpen', true);
        $action->verify();
    });

    it('rejects unavailable wrestlers and referees', function (): void {
        // Arrange
        $unavailableWrestler = Wrestler::factory()->retired()->create();
        $availableWrestler = Wrestler::factory()->bookable()->create();
        $unavailableReferee = Referee::factory()->retired()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
        $modal->assertHasErrors([
            'form.competitors.0.wrestlers.0',
            'form.referees.0',
        ]);
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('rejects an unavailable tag team', function (): void {
        // Arrange
        $unavailableTagTeam = TagTeam::factory()->retired()->create();
        $availableTagTeam = TagTeam::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
        $modal->assertHasErrors(['form.competitors.0.tag_teams.0']);
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('rejects inactive stipulations and titles', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $wrestlerIds = $wrestlers->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $inactiveStipulation = MatchStipulation::factory()->inactive()->create();
        $inactiveTitle = Title::factory()->inactive()->singles()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
        $modal->assertHasErrors([
            'form.matchStipulationId' => 'exists',
            'form.titles.0',
        ]);
    });

    it('rejects a competitor selected on multiple sides', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
        $modal->assertHasErrors([
            'form.competitors.0.wrestlers.0' => 'distinct',
            'form.competitors.1.wrestlers.0' => 'distinct',
        ]);
    });

    it('translates invalid match composition into form feedback', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(4)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
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

        // Assert
        $modal
            ->assertHasErrors(['form.configuration'])
            ->assertSee('The [6 Man Tag Team] match requires a 3-on-3 roster-member composition.')
            ->assertNotDispatched('closeModal');
        expect(EventMatch::query()->whereBelongsTo($this->event)->doesntExist())->toBeTrue();
    });

    it('requires the minimum number of individual entrants', function (MatchType $matchType, int $entrantCount): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count($entrantCount)->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', $matchType);
        $modal->set('form.competitors.0.wrestlers', $wrestlers->modelKeys());
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.competitors.0.wrestlers' => 'min']);
    })->with([
        'battle royal' => [MatchType::BattleRoyal, 2],
        'royal rumble' => [MatchType::RoyalRumble, 9],
    ]);

    it('updates an existing match configuration', function (): void {
        // Arrange
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

        // Act
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

        // Assert
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

    it('resets an edited match when reopening in create mode', function (): void {
        // Arrange
        $match = EventMatch::factory()->for($this->event)->create([
            'match_type' => MatchType::Singles,
            'preview' => 'Unsaved edit.',
        ]);
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal', $match->id);
        $modal->call('openModal');

        // Assert
        $modal
            ->assertSet('form.matchType', null)
            ->assertSet('form.matchStipulationId', null)
            ->assertSet('form.competitors', [])
            ->assertSet('form.referees', [])
            ->assertSet('form.titles', [])
            ->assertSet('form.preview', '');
    });

    it('generates valid dummy data that can create a match', function (): void {
        // Arrange
        Wrestler::factory()->count(2)->bookable()->create();
        Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('matchCreated')
            ->assertSet('isModalOpen', false);
        expect(EventMatch::query()->whereBelongsTo($this->event)->count())->toBe(1);
    });
});

/**
 * Fill the match form from match data the way a user would select it.
 *
 * @param  Testable<FormModal>  $modal
 */
function selectMatchDataInForm(Testable $modal, EventMatchData $data): void
{
    $modal->set('form.matchType', $data->matchType);
    $modal->set([
        'form.competitors' => $data->sides->values()->map(fn (array $side): array => array_filter([
            'wrestlers' => array_map(fn (Wrestler $wrestler): int => $wrestler->id, $side['wrestlers'] ?? []),
            'tag_teams' => array_map(fn (TagTeam $tagTeam): int => $tagTeam->id, $side['tag_teams'] ?? []),
        ]))->all(),
        'form.referees' => $data->referees->modelKeys(),
        'form.titles' => $data->titles->modelKeys(),
    ]);
}

describe('booking for a global administrator without a promotion context', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
        [$this->promotion, $this->foreignPromotion] = Promotion::factory()->count(2)->create()->all();
        $this->event = Event::factory()->for($this->promotion, 'promotion')->create();
    });

    afterEach(function (): void {
        resolve(PromotionContextService::class)->clear();
    });

    it('rejects :dataset of another promotion', function (Closure $matchData, string $entityType, string $field): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->call('openModal');
        selectMatchDataInForm($modal, $matchData($this->promotion, $this->foreignPromotion));

        // Act
        $modal->call('save');

        // Assert
        $modal->assertHasErrors([$field]);
        expect(EventMatch::query()->whereBelongsTo($this->event)->exists())->toBeFalse();
    })->with('cross promotion match bookings');

    it('books the roster and titles of the event promotion', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->bookable()->for($this->promotion, 'promotion')->count(2)->create();
        $referee = Referee::factory()->bookable()->for($this->promotion, 'promotion')->create();
        $title = Title::factory()->active()->singles()->for($this->promotion, 'promotion')->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$wrestlers->modelKeys()[0]]],
                ['wrestlers' => [$wrestlers->modelKeys()[1]]],
            ],
            'form.referees' => [$referee->id],
            'form.titles' => [$title->id],
        ]);

        // Act
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors();
        expect(EventMatch::query()->whereBelongsTo($this->event)->sole()->titles()->pluck('titles.id')->all())
            ->toBe([$title->id]);
    });

    it('validates an edited match against the promotion of its own event', function (): void {
        // Arrange
        $foreignEvent = Event::factory()->for($this->foreignPromotion, 'promotion')->create();
        $match = EventMatch::factory()->for($this->event)->create(['match_type' => MatchType::Singles]);
        $foreignWrestlers = Wrestler::factory()->bookable()->for($this->foreignPromotion, 'promotion')->count(2)->create();
        $foreignReferee = Referee::factory()->bookable()->for($this->foreignPromotion, 'promotion')->create();
        $modal = livewire(FormModal::class, ['eventId' => $foreignEvent->id]);
        $modal->call('openModal', $match->id);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [$foreignWrestlers->modelKeys()[0]]],
                ['wrestlers' => [$foreignWrestlers->modelKeys()[1]]],
            ],
            'form.referees' => [$foreignReferee->id],
        ]);

        // Act
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.competitors.0.wrestlers.0', 'form.competitors.1.wrestlers.0', 'form.referees.0']);
        expect($match->competitors()->exists())->toBeFalse();
    });

    it('leaves the card readable for the event promotion members after :dataset was refused', function (Closure $matchData): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->call('openModal');
        selectMatchDataInForm($modal, $matchData($this->promotion, $this->foreignPromotion));
        $modal->call('save');
        $member = basicUser();
        $this->promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
        actingAs($member);
        $context = resolve(PromotionContextService::class);
        $context->set($this->promotion);
        $context->enforce();

        // Act
        $table = livewire(MatchesTable::class, ['eventId' => $this->event->id]);

        // Assert
        $table->assertOk();
        expect(EventMatch::query()->whereBelongsTo($this->event)->exists())->toBeFalse();
    })->with('cross promotion match bookings');
});

it('forbids :dataset from opening the match form', function (bool $authenticated, bool $editing, int $status): void {
    // Arrange
    $event = Event::factory()->create();
    $match = $editing
        ? EventMatch::factory()->for($event)->create()
        : null;

    if ($authenticated) {
        actingAs(basicUser());
    }

    // Act
    $modal = livewire(FormModal::class, ['eventId' => $event->id, 'modelId' => $match?->id]);

    // Assert
    $modal->assertStatus($status);
})->with([
    'a guest creating' => [false, false, 403],
    'a basic user creating' => [true, false, 403],
    'a guest editing' => [false, true, 403],
    'a basic user editing' => [true, true, 404],
]);
