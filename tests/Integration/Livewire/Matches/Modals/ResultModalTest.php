<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Livewire\Matches\Modals\ResultModal;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;

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
        $wrestler = Wrestler::factory()->create(['name' => "Competitor {$position}"]);
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

it('requires an administrator to record a result', function (bool $authenticated): void {
    // Arrange
    [$match] = createMatchWithResultCompetitors();

    if ($authenticated) {
        actingAs(basicUser());
    }

    $modal = livewire(ResultModal::class, ['matchId' => $match->id]);

    // Act
    $modal->set('form.finish', MatchFinish::TimeLimitDraw->value);
    $modal->call('save');

    // Assert
    $modal->assertForbidden();
})->with([
    'guest' => false,
    'authenticated non-administrator' => true,
]);
