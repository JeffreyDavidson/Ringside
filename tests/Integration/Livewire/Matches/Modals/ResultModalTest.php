<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Titles\TitleType;
use App\Livewire\Matches\Modals\ResultModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @return array{EventMatch, list<MatchCompetitor>}
 */
function createMatchWithResultCompetitors(MatchType $type = MatchType::Singles, int $count = 2): array
{
    $match = EventMatch::factory()->create(['match_type' => $type]);
    $competitors = [];

    foreach (range(1, $count) as $position) {
        $side = MatchSide::factory()->for($match, 'match')->create(['position' => $position]);
        $wrestler = Wrestler::factory()->bookable()->create(['name' => "Competitor {$position}"]);
        $competitors[] = MatchCompetitor::factory()->create([
            'match_id' => $match->id,
            'match_side_id' => $side->id,
            'competitor_type' => $wrestler->getMorphClass(),
            'competitor_id' => $wrestler->id,
            'entry_order' => $type === MatchType::RoyalRumble ? $position : null,
        ]);
    }

    return [$match, $competitors];
}

describe('authorized result recording', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    it('records an ordinary match result', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors();
        $winningSide = $competitors[0]->side;
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.finish', MatchFinish::Pinfall->value);
        $modal->set('form.winningSideId', $winningSide->id);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
        expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
            ->and($match->winning_side_id)->toBe($winningSide->id);
    });

    it('records a draw without a winning side', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors();
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.winningSideId', $competitors[0]->match_side_id);
        $modal->set('form.finish', MatchFinish::TimeLimitDraw->value);
        $modal->call('save');

        // Assert
        $modal
            ->assertSet('form.winningSideId', null)
            ->assertHasNoErrors();
        expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
            ->and($match->winning_side_id)->toBeNull();
    });

    it('records a complete elimination match result', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors(MatchType::BattleRoyal, 3);
        $winner = $competitors[2];
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.finish', MatchFinish::Stipulation->value);
        $modal->set('form.winningSideId', $winner->match_side_id);
        $modal->set("form.eliminations.{$competitors[0]->id}.order", '1');
        $modal->set("form.eliminations.{$competitors[0]->id}.eliminatedById", (string) $winner->id);
        $modal->set("form.eliminations.{$competitors[1]->id}.order", '2');
        $modal->set("form.eliminations.{$competitors[1]->id}.eliminatedById", (string) $winner->id);
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors();
        expect($competitors[0]->refresh()->elimination_order)->toBe(1)
            ->and($competitors[0]->eliminated_by_match_competitor_id)->toBe($winner->id)
            ->and($competitors[1]->refresh()->elimination_order)->toBe(2)
            ->and($winner->refresh()->elimination_order)->toBeNull();
    });

    it('loads an existing result for correction', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors(MatchType::BattleRoyal, 3);
        $match->update([
            'match_finish' => MatchFinish::Stipulation,
            'winning_side_id' => $competitors[2]->match_side_id,
        ]);
        $competitors[0]->forceFill([
            'elimination_order' => 1,
            'eliminated_by_match_competitor_id' => $competitors[2]->id,
        ])->save();

        // Act
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Assert
        $modal
            ->assertSet('form.finish', MatchFinish::Stipulation->value)
            ->assertSet('form.winningSideId', $competitors[2]->match_side_id)
            ->assertSet("form.eliminations.{$competitors[0]->id}.order", 1)
            ->assertSee('Correct Match Result');
    });

    it('requires a winning side for a decisive finish', function (): void {
        // Arrange
        [$match] = createMatchWithResultCompetitors();
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.finish', MatchFinish::Pinfall->value);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.winningSideId' => ['required']])
            ->assertNotDispatched('closeModal');
        expect($match->refresh()->match_finish)->toBeNull();
    });

    it('keeps the selected winning side when the finish is cleared', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors();
        $winningSideId = $competitors[0]->match_side_id;
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);
        $modal->set('form.finish', MatchFinish::Pinfall->value);
        $modal->set('form.winningSideId', $winningSideId);

        // Act
        $modal->set('form.finish', '');

        // Assert
        $modal
            ->assertSet('form.finish', '')
            ->assertSet('form.winningSideId', $winningSideId);
    });

    it('reports a rejected result as an outcome error without closing the modal', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors(MatchType::BattleRoyal, 3);
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.finish', MatchFinish::Stipulation->value);
        $modal->set('form.winningSideId', $competitors[0]->match_side_id);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['outcome'])
            ->assertNotDispatched('refreshDatatable')
            ->assertNotDispatched('closeModal');
        expect($match->refresh()->match_finish)->toBeNull()
            ->and($match->winning_side_id)->toBeNull();
    });

    it('reports a title result recorded out of date order as an outcome error', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors();
        $match->event->update(['date' => now()->subDays(10)]);
        $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
        $match->titles()->attach($title);
        TitleChampionship::factory()
            ->for($title)
            ->forWrestler(Wrestler::factory()->create())
            ->wonOn(now()->subDays(5)->toDateTimeString())
            ->create();
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.finish', MatchFinish::Pinfall->value);
        $modal->set('form.winningSideId', $competitors[0]->match_side_id);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['outcome'])
            ->assertSee('record results in date order')
            ->assertNotDispatched('refreshDatatable')
            ->assertNotDispatched('closeModal');
        expect($match->refresh()->match_finish)->toBeNull()
            ->and($title->championships()->count())->toBe(1);
    });

    it('hides elimination inputs for ordinary matches', function (): void {
        // Arrange
        [$match] = createMatchWithResultCompetitors();

        // Act
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Assert
        $modal->assertDontSee('Eliminations');
    });

    it('renders elimination inputs for supported match types', function (): void {
        // Arrange
        [$match] = createMatchWithResultCompetitors(MatchType::RoyalRumble, 10);

        // Act
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Assert
        $modal
            ->assertSee('Eliminations')
            ->assertSee('Competitor 1')
            ->assertSee('Competitor 10');
    });

    it('builds side and competitor option labels from match entrants', function (): void {
        // Arrange
        [$match, $competitors] = createMatchWithResultCompetitors();

        // Act
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Assert
        $modal
            ->assertSet('sideOptions', [
                $competitors[0]->match_side_id => 'Competitor 1',
                $competitors[1]->match_side_id => 'Competitor 2',
            ])
            ->assertSet('competitorOptions', [
                $competitors[0]->id => 'Competitor 1',
                $competitors[1]->id => 'Competitor 2',
            ]);
    });

    it('rejects elimination data for a competitor outside the match', function (): void {
        // Arrange
        [$match] = createMatchWithResultCompetitors(MatchType::BattleRoyal, 3);
        $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

        // Act
        $modal->set('form.finish', MatchFinish::Stipulation->value);
        $modal->set('form.eliminations.999999', [
            'order' => 1,
            'eliminatedById' => null,
        ]);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.eliminations' => ['array']])
            ->assertNotDispatched('closeModal');
        expect($match->refresh()->match_finish)->toBeNull();
    });
});

it('refuses to save a result after the manager was :dataset since opening the modal', function (MembershipRole $role, MembershipStatus $status): void {
    // Arrange
    [$match, $competitors] = createMatchWithResultCompetitors();
    $promotion = Promotion::factory()->create();
    Event::query()->whereKey($match->event_id)->update(['promotion_id' => $promotion->id]);
    Wrestler::query()->update(['promotion_id' => $promotion->id]);
    $manager = actingAsPromotionMember($promotion, MembershipRole::Manager);
    $modal = livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.finish', MatchFinish::Pinfall->value)
        ->set('form.winningSideId', $competitors[0]->match_side_id);
    changePromotionMembership($promotion, $manager, $role, $status);

    // Act
    $modal->call('save');

    // Assert
    $modal->assertForbidden();

    expect($match->refresh()->match_finish)->toBeNull()
        ->and($match->winning_side_id)->toBeNull();
})->with([
    'demoted to member' => [MembershipRole::Member, MembershipStatus::Active],
    'suspended' => [MembershipRole::Manager, MembershipStatus::Suspended],
]);

it('requires an administrator to open the result modal', function (bool $authenticated, int $status): void {
    // Arrange
    [$match] = createMatchWithResultCompetitors();

    if ($authenticated) {
        actingAs(basicUser());
    }

    // Act
    $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

    // Assert
    $modal->assertStatus($status);
})->with([
    'guest' => [false, 403],
    'authenticated non-administrator' => [true, 404],
]);
