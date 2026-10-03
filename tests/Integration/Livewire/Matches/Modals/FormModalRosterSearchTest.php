<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

afterEach(function (): void {
    resolve(PromotionContextService::class)->clear();
});

describe('searching the roster from the match form', function (): void {
    it('returns bookable matches for the requested kind', function (string $kind, string $term, string $expectedName): void {
        // Arrange
        $event = Event::factory()->create();
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        Wrestler::factory()->injured()->create(['name' => 'Ricky Injured']);
        TagTeam::factory()->bookable()->create(['name' => 'The Rockers']);
        Referee::factory()->bookable()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $options = $modal->instance()->searchRoster($kind, $term);

        // Assert
        expect(array_column($options, 'name'))->toBe([$expectedName]);
    })->with([
        'wrestlers' => ['wrestlers', 'ricky', 'Ricky Steamboat'],
        'tag teams' => ['tag_teams', 'rock', 'The Rockers'],
        'referees' => ['referees', 'HEBNER', 'Earl Hebner'],
    ]);

    it('returns nothing for an unknown kind', function (): void {
        // Arrange
        $event = Event::factory()->create();
        Wrestler::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $options = $modal->instance()->searchRoster('managers', '');

        // Assert
        expect($options)->toBe([]);
    });

    it('returns the options as the action result', function (): void {
        // Arrange
        $event = Event::factory()->create();
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $modal->call('searchRoster', 'wrestlers', 'ricky');

        // Assert
        $modal->assertReturned(fn (array $options): bool => count($options) === 1);
    });

    it('does not let another promotions roster leak into the results', function (): void {
        // Arrange
        [$promotion, $otherPromotion] = Promotion::factory()->count(2)->create()->all();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'Our Wrestler']);
        Wrestler::factory()->for($otherPromotion, 'promotion')->bookable()->create(['name' => 'Their Wrestler']);
        $context = resolve(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $options = $modal->instance()->searchRoster('wrestlers', 'Wrestler');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Our Wrestler']);
    });

    it('offers a global administrator only the roster of the event promotion', function (): void {
        // Arrange
        [$promotion, $otherPromotion] = Promotion::factory()->count(2)->create()->all();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'Our Wrestler']);
        Wrestler::factory()->for($otherPromotion, 'promotion')->bookable()->create(['name' => 'Their Wrestler']);
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $options = $modal->instance()->searchRoster('wrestlers', 'Wrestler');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Our Wrestler']);
    });

    it('searches the roster of the edited match event whatever event the modal was given', function (): void {
        // Arrange
        [$promotion, $otherPromotion] = Promotion::factory()->count(2)->create()->all();
        $match = EventMatch::factory()->for(Event::factory()->for($promotion, 'promotion'))->create();
        $otherEvent = Event::factory()->for($otherPromotion, 'promotion')->create();
        Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'Our Wrestler']);
        Wrestler::factory()->for($otherPromotion, 'promotion')->bookable()->create(['name' => 'Their Wrestler']);
        $modal = livewire(FormModal::class, ['eventId' => $otherEvent->id]);
        $modal->call('openModal', $match->id);

        // Act
        $options = $modal->instance()->searchRoster('wrestlers', 'Wrestler');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Our Wrestler']);
    });

    it('refuses users who cannot create matches', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);
        actingAs(basicUser());

        // Act
        $search = fn (): array => $modal->instance()->searchRoster('wrestlers', '');

        // Assert
        expect($search)->toThrow(AuthorizationException::class);
    });
});

describe('rendering the selected records', function (): void {
    it('shows the names of competitors and referees that are no longer bookable when editing', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $retiredWrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
        $trashedWrestler = Wrestler::factory()->bookable()->create(['name' => 'Trashed Wrestler']);
        $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Team']);
        $injuredReferee = Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Official']);
        $match = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$retiredWrestler, $trashedWrestler, $suspendedTagTeam])
            ->create(['match_type' => MatchType::SixManTagTeam]);
        $match->referees()->attach($injuredReferee);
        $trashedWrestler->delete();

        // Act
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);
        $modal->call('openModal', $match->id);

        // Assert
        $labels = $modal->instance()->selectedRosterLabels;
        expect(array_column($labels['wrestlers'], 'name'))->toContain('Retired Wrestler', 'Trashed Wrestler')
            ->and(array_column($labels['tag_teams'], 'name'))->toBe(['Suspended Team'])
            ->and(array_column($labels['referees'], 'name'))->toBe(['Injured Official']);
        $modal
            ->assertSuccessful()
            ->assertSee('Retired Wrestler')
            ->assertSee('Suspended Team')
            ->assertSee('Injured Official');
    });

    it('resolves no labels for an empty form', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $modal->call('openModal');

        // Assert
        expect($modal->instance()->selectedRosterLabels)->toBe([
            'wrestlers' => [],
            'tag_teams' => [],
            'referees' => [],
        ]);
    });

    it('renders the same number of options regardless of roster size', function (): void {
        // Arrange
        $event = Event::factory()->create();
        Wrestler::factory()->bookable()->count(5)->create();
        $small = livewire(FormModal::class, ['eventId' => $event->id]);
        $small->call('openModal')->set('form.matchType', MatchType::BattleRoyal);
        $smallHtml = $small->html();

        Wrestler::factory()->bookable()->count(55)->create();
        TagTeam::factory()->bookable()->count(5)->create();
        Referee::factory()->bookable()->count(20)->create();

        // Act
        $large = livewire(FormModal::class, ['eventId' => $event->id]);
        $large->call('openModal')->set('form.matchType', MatchType::BattleRoyal);
        $largeHtml = $large->html();

        // Assert
        expect(substr_count($largeHtml, '<option'))->toBe(substr_count($smallHtml, '<option'))
            ->and($largeHtml)->toHaveLength(strlen($smallHtml));
    });
});

describe('server-side validation stays the authority', function (): void {
    it('rejects a forged competitor id that the search would never offer', function (string $unbookableState): void {
        // Arrange
        $event = Event::factory()->create();
        $bookable = Wrestler::factory()->bookable()->create();
        $forged = Wrestler::factory()->{$unbookableState}()->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set('form.competitors.0.wrestlers', [$bookable->id]);
        $modal->set('form.competitors.1.wrestlers', [$forged->id]);
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.competitors.1.wrestlers.0']);
        expect(EventMatch::query()->whereBelongsTo($event)->doesntExist())->toBeTrue();
    })->with(['injured', 'suspended', 'retired', 'unemployed', 'withFutureEmployment']);

    it('rejects a forged id that belongs to another promotion', function (): void {
        // Arrange
        [$promotion, $otherPromotion] = Promotion::factory()->count(2)->create()->all();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        $ours = Wrestler::factory()->for($promotion, 'promotion')->bookable()->create();
        $theirs = Wrestler::factory()->for($otherPromotion, 'promotion')->bookable()->create();
        $referee = Referee::factory()->for($promotion, 'promotion')->bookable()->create();
        $context = resolve(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set('form.competitors.0.wrestlers', [$ours->id]);
        $modal->set('form.competitors.1.wrestlers', [$theirs->id]);
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.competitors.1.wrestlers.0']);
    });

    it('rejects a forged referee id', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $first = Wrestler::factory()->bookable()->create();
        $second = Wrestler::factory()->bookable()->create();
        $forgedReferee = Referee::factory()->injured()->create();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::Singles);
        $modal->set('form.competitors.0.wrestlers', [$first->id]);
        $modal->set('form.competitors.1.wrestlers', [$second->id]);
        $modal->set('form.referees', [$forgedReferee->id]);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.referees.0']);
    });

    it('rejects a forged tag team id', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $forgedTagTeam = TagTeam::factory()->suspended()->create();
        $wrestlers = Wrestler::factory()->bookable()->count(2)->create();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $modal->call('openModal');
        $modal->set('form.matchType', MatchType::TagTeam);
        $modal->set('form.competitors.0.tag_teams', [$forgedTagTeam->id]);
        $modal->set('form.competitors.1.wrestlers', $wrestlers->modelKeys());
        $modal->set('form.referees', [$referee->id]);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.competitors.0.tag_teams.0']);
    });
});
