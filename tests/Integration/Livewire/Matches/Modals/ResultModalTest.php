<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Livewire\Matches\Modals\ResultModal;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

/**
 * @return array{EventMatch, list<MatchCompetitor>}
 */
function matchWithResultCompetitors(MatchType $type = MatchType::Singles, int $count = 2): array
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

it('records an ordinary match result', function () {
    [$match, $competitors] = matchWithResultCompetitors();
    $winningSide = $competitors[0]->side;

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.finish', MatchFinish::Pinfall->value)
        ->set('form.winningSideId', $winningSide->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('refreshDatatable')
        ->assertDispatched('closeModal');

    expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($match->winning_side_id)->toBe($winningSide->id);
});

it('records a draw without a winning side', function () {
    [$match, $competitors] = matchWithResultCompetitors();

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.winningSideId', $competitors[0]->match_side_id)
        ->set('form.finish', MatchFinish::TimeLimitDraw->value)
        ->assertSet('form.winningSideId', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($match->winning_side_id)->toBeNull();
});

it('records a complete elimination match result', function () {
    [$match, $competitors] = matchWithResultCompetitors(MatchType::BattleRoyal, 3);
    $winner = $competitors[2];

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.finish', MatchFinish::Stipulation->value)
        ->set('form.winningSideId', $winner->match_side_id)
        ->set("form.eliminations.{$competitors[0]->id}.order", '1')
        ->set("form.eliminations.{$competitors[0]->id}.eliminatedById", (string) $winner->id)
        ->set("form.eliminations.{$competitors[1]->id}.order", '2')
        ->set("form.eliminations.{$competitors[1]->id}.eliminatedById", (string) $winner->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($competitors[0]->refresh()->elimination_order)->toBe(1)
        ->and($competitors[0]->eliminated_by_match_competitor_id)->toBe($winner->id)
        ->and($competitors[1]->refresh()->elimination_order)->toBe(2)
        ->and($winner->refresh()->elimination_order)->toBeNull();
});

it('loads an existing result for correction', function () {
    [$match, $competitors] = matchWithResultCompetitors(MatchType::BattleRoyal, 3);
    $match->update([
        'match_finish' => MatchFinish::Stipulation,
        'winning_side_id' => $competitors[2]->match_side_id,
    ]);
    $competitors[0]->forceFill([
        'elimination_order' => 1,
        'eliminated_by_match_competitor_id' => $competitors[2]->id,
    ])->save();

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->assertSet('form.finish', MatchFinish::Stipulation->value)
        ->assertSet('form.winningSideId', $competitors[2]->match_side_id)
        ->assertSet("form.eliminations.{$competitors[0]->id}.order", 1)
        ->assertSee('Correct Match Result');
});

it('requires a winning side for a decisive finish', function () {
    [$match] = matchWithResultCompetitors();

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.finish', MatchFinish::Pinfall->value)
        ->call('save')
        ->assertHasErrors(['form.winningSideId' => ['required']])
        ->assertNotDispatched('closeModal');

    expect($match->refresh()->match_finish)->toBeNull();
});

it('hides elimination inputs for ordinary matches', function () {
    [$match] = matchWithResultCompetitors();

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->assertDontSee('Eliminations');
});

it('renders elimination inputs for supported match types', function () {
    [$match] = matchWithResultCompetitors(MatchType::RoyalRumble, 10);

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->assertSee('Eliminations')
        ->assertSee('Competitor 1')
        ->assertSee('Competitor 10');
});

it('reuses the computed match across result option consumers', function () {
    [$match] = matchWithResultCompetitors();
    $modal = livewire(ResultModal::class, ['matchId' => $match->id])->instance();

    if (! $modal instanceof ResultModal) {
        throw new LogicException('Expected the result modal component instance.');
    }

    unset($modal->match);
    DB::flushQueryLog();
    DB::enableQueryLog();

    $modal->sideOptions();
    $initialQueries = DB::getQueryLog();

    $modal->competitorOptions();
    $modal->getModalTitle();

    expect($initialQueries)->not->toBeEmpty()
        ->and(DB::getQueryLog())->toHaveCount(count($initialQueries));
});

it('requires an administrator to record a result', function () {
    [$match] = matchWithResultCompetitors();
    actingAs(User::factory()->create());

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.finish', MatchFinish::TimeLimitDraw->value)
        ->call('save')
        ->assertForbidden();
});

it('rejects elimination data for a competitor outside the match', function () {
    [$match] = matchWithResultCompetitors(MatchType::BattleRoyal, 3);

    livewire(ResultModal::class, ['matchId' => $match->id])
        ->set('form.finish', MatchFinish::Stipulation->value)
        ->set('form.eliminations.999999', [
            'order' => 1,
            'eliminatedById' => null,
        ])
        ->call('save')
        ->assertHasErrors(['form.eliminations' => ['array']])
        ->assertNotDispatched('closeModal');

    expect($match->refresh()->match_finish)->toBeNull();
});
