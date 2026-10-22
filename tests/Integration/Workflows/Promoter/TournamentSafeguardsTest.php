<?php

declare(strict_types=1);
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Actions\Events\UpdateAction;
use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\AddTitlesToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Actions\Matches\DeleteAction;
use App\Actions\Matches\RecordResultAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\CancelTournamentAction;
use App\Actions\Tournaments\PlanTournamentBoutAction;
use App\Actions\Tournaments\ReplaceTournamentEntryAction;
use App\Actions\Tournaments\ResetTournamentBranchAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Data\Events\EventData;
use App\Data\Events\EventPackData;
use App\Data\Matches\EventMatchData;
use App\Data\Matches\MatchResultData;
use App\Data\Tournaments\TournamentBookingData;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Enums\Titles\TitleType;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Tournaments\TournamentBracketService;
use App\ValueObjects\MatchSides;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\Database\Eloquent\Collection;

it('protects linked bookings from ordinary match and event deletion', function (): void {
    $world = tournamentFixture();
    $match = bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
    expect(fn () => resolve(DeleteAction::class)->handle($match))->toThrow(TournamentException::class)
        ->and(fn () => resolve(App\Actions\Events\DeleteAction::class)->handle($world['event']))->toThrow(TournamentException::class)
        ->and($match->refresh()->trashed())->toBeFalse()
        ->and($world['event']->refresh()->trashed())->toBeFalse();
});
it('refuses played downstream resets without changing either result', function (): void {
    $world = tournamentFixture();
    $nodes = $world['tournament']->nodes()->get();
    $first = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1));
    recordTournamentWinner($first, $world['tournament']);
    $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
    recordTournamentWinner($second, $world['tournament']);
    $final = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 2));
    recordTournamentWinner($final, $world['tournament']);
    expect(fn () => resolve(ResetTournamentBranchAction::class)->handle($nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1), $world['tournament']->refresh()->version, 'Correct the source'))->toThrow(TournamentException::class)
        ->and($final->refresh()->trashed())->toBeFalse()
        ->and($first->refresh()->match_finish)->toBe(MatchFinish::Pinfall);
});
it('does not permit changing a historical draw into a new qualifier after its rematch', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $draw = bookTournamentNode($world, $node);
    recordTournamentWinner($draw, $world['tournament'], MatchFinish::NoDecision);
    $rematch = bookTournamentNode($world, $node);
    recordTournamentWinner($rematch, $world['tournament']);
    expect(fn () => resolve(RecordResultAction::class)->handle($draw, new MatchResultData(MatchFinish::Pinfall, $draw->sides()->firstOrFail(), collect(), $world['tournament']->refresh()->version)))->toThrow(TournamentException::class)
        ->and($draw->refresh()->match_finish)->toBe(MatchFinish::NoDecision);
});
it('supports across-event advancement and refuses unscheduling the dependent booking', function (): void {
    $world = tournamentFixture(repeat: false);
    $nodes = $world['tournament']->nodes()->get();
    $first = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1));
    recordTournamentWinner($first, $world['tournament']);
    $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
    recordTournamentWinner($second, $world['tournament']);
    $future = Event::factory()->for($world['promotion'])->create(['date' => now()->addDays(2)]);
    $final = resolve(BookTournamentMatchAction::class)->handle($nodes->sole(fn ($n): bool => $n->round === 2), $world['tournament']->refresh()->version, new TournamentBookingData($future, $world['referees']));
    expect(fn () => resolve(UpdateAction::class)->handle($future, new EventData($future->name, null, null, $future->preview)))->toThrow(TournamentException::class)
        ->and($future->refresh()->date)->not->toBeNull()
        ->and($final->refresh()->trashed())->toBeFalse();
});
it('rechecks standalone assignments and refuses a non-final title stake', function (): void {
    $world = tournamentFixture();
    $match = bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
    $third = Wrestler::factory()->employed()->for($world['promotion'])->create();
    expect(fn () => resolve(AddWrestlersToMatchAction::class)->handle($match, collect([$third]), 1))->toThrow(TournamentException::class);
    $title = Title::factory()->active()->for($world['promotion'])->create();
    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title])))->toThrow(TournamentException::class)
        ->and($match->competitors()->count())->toBe(2)
        ->and($match->titles()->count())->toBe(0);
});
it('allows a linked unplayed match edit without swapping its bracket entrants', function (): void {
    $world = tournamentFixture();
    $match = bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
    $competitors = $match->wrestlers()->get();
    $data = new EventMatchData(MatchType::Singles, $world['referees'], new Collection, new MatchSides(collect([1 => ['wrestlers' => [$competitors->sole(fn ($c): bool => $c->id === $match->sides()->where('position', 1)->firstOrFail()->competitors()->firstOrFail()->competitor_id)]], 2 => ['wrestlers' => [$competitors->sole(fn ($c): bool => $c->id === $match->sides()->where('position', 2)->firstOrFail()->competitors()->firstOrFail()->competitor_id)]]])), 'Revised preview', tournamentVersion: $world['tournament']->refresh()->version);
    resolve(UpdateMatchAction::class)->handle($match, $data);
    expect($match->refresh()->preview)->toBe('Revised preview')->and($world['tournament']->refresh()->version)->toBe(3);
});

it('returns the existing real match for an identical booking retry', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $version = $world['tournament']->version;
    $first = bookTournamentNode($world, $node);
    $retry = resolve(BookTournamentMatchAction::class)->handle($node, $version, new TournamentBookingData($world['event'], $world['referees']));
    expect($retry->id)->toBe($first->id)->and($world['event']->matches()->count())->toBe(1)->and($node->links()->count())->toBe(1);
});
it('refuses moving a draw rematch earlier than its retained attempt', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    recordTournamentWinner($match, $world['tournament'], MatchFinish::TimeLimitDraw);
    $earlier = Event::factory()->for($world['promotion'])->create(['date' => $world['event']->date?->copy()->subDay()]);
    expect(fn () => resolve(BookTournamentMatchAction::class)->handle($node, $world['tournament']->refresh()->version, new TournamentBookingData($earlier, $world['referees'])))->toThrow(TournamentException::class)
        ->and($node->links()->count())->toBe(1)
        ->and($earlier->matches()->count())->toBe(0);
});
it('invalidates tournament review when a tentative event changes or is deleted and restored', function (): void {
    $world = tournamentFixture();
    $event = Event::factory()->for($world['promotion'])->create(['date' => now()->addWeek()]);
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    resolve(PlanTournamentBoutAction::class)->handle($node, $event, $t->version);
    $before = $t->refresh()->version;
    resolve(UpdateAction::class)->handle($event, new EventData($event->name, now()->addWeeks(2), null, null));
    expect($t->refresh()->version)->toBe($before + 1);
    $before = $t->version;
    resolve(App\Actions\Events\DeleteAction::class)->handle($event);
    expect($t->refresh()->version)->toBe($before + 1);
    resolve(RestoreAction::class)->handle($event);
    expect($t->refresh()->version)->toBe($before + 2)->and($event->refresh()->trashed())->toBeFalse();
});
it('requires a refresh when a planned event has been deleted since it was selected', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $selected = Event::query()->findOrFail($world['event']->id);
    resolve(App\Actions\Events\DeleteAction::class)->handle($world['event']);
    expect(fn () => resolve(PlanTournamentBoutAction::class)->handle($node, $selected, $world['tournament']->version))->toThrow(RecordChangedException::class)
        ->and($node->refresh()->planned_event_id)->toBeNull();
});
it('refuses changing a qualifier after an administrative descendant has advanced', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $nodes = $t->nodes()->get();
    $source = $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1);
    $first = bookTournamentNode($world, $source);
    recordTournamentWinner($first, $t);
    $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
    recordTournamentWinner($second, $t);
    $final = $nodes->sole(fn ($n): bool => $n->round === 2);
    $bout = resolve(TournamentBracketService::class)->bout($final);
    resolve(WithdrawTournamentEntryAction::class)->handle($bout->second ?? throw new RuntimeException('Expected finalist'), $t->refresh()->version, 'Cannot compete');
    resolve(AwardTournamentWalkoverAction::class)->handle($final, $t->refresh()->version, 'Opponent withdrew');
    expect(fn () => resolve(RecordResultAction::class)->handle($first, new MatchResultData(MatchFinish::Pinfall, $first->sides()->where('position', 2)->firstOrFail(), collect(), $t->refresh()->version)))->toThrow(TournamentException::class)
        ->and($final->refresh()->walkover_entry_id)->not->toBeNull();
});
it('preserves cancelled results against subsequent edits', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $match = bookTournamentNode($world, $t->nodes()->firstOrFail());
    recordTournamentWinner($match, $t);
    resolve(CancelTournamentAction::class)->handle($t, $t->refresh()->version, 'Cancelled remaining rounds');
    expect(fn () => resolve(RecordResultAction::class)->handle($match, new MatchResultData(MatchFinish::NoDecision, null, collect(), $t->refresh()->version)))->toThrow(TournamentException::class)
        ->and($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall);
});

it('refuses resetting a bout that has already started in the live show', function (): void {
    \Pest\Laravel\actingAs(administrator());
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $event = $world['event'];
    $event->update(['date' => now()]);
    $match = bookTournamentNode($world, $node);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    resolve(TransitionLiveItemAction::class)->handle($event, $run->version, 'match:'.$match->id, LiveItemOperation::Start, signedInUser());
    expect(fn () => resolve(ResetTournamentBranchAction::class)->handle($node, $t->refresh()->version, 'Unbook started bout', true))->toThrow(TournamentException::class)
        ->and($match->refresh()->trashed())->toBeFalse()
        ->and($node->links()->firstOrFail()->active_node_id)->toBe($node->id);
});

it('refuses moving a real tournament booking through tentative planning', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    expect(fn () => resolve(PlanTournamentBoutAction::class)->handle($node, null, $world['tournament']->refresh()->version))->toThrow(TournamentException::class)
        ->and($node->refresh()->planned_event_id)->toBe($match->event_id);
});
it('rolls back a replacement reset when the replacement is not employed', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $entry = $t->entries()->firstOrFail();
    $match = bookTournamentNode($world, $t->nodes()->firstOrFail());
    $replacement = Wrestler::factory()->for($world['promotion'])->create();
    expect(fn () => resolve(ReplaceTournamentEntryAction::class)->handle($entry, $replacement->id, $t->refresh()->version, 'Replace entry'))->toThrow(TournamentException::class)
        ->and($match->refresh()->trashed())->toBeFalse()
        ->and($entry->refresh()->competitor_id)->not->toBe($replacement->id);
});
it('refuses stale ordinary edits and extra bracket opponents without replacing assignments', function (bool $stale): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    $competitors = $match->wrestlers()->get();
    $extra = Wrestler::factory()->employed()->for($world['promotion'])->create();
    $first = $competitors->sole('id', $t->entries()->where('seed', $node->first_seed)->firstOrFail()->competitor_id);
    $second = $competitors->sole('id', $t->entries()->where('seed', $node->second_seed)->firstOrFail()->competitor_id);
    $data = new EventMatchData(MatchType::Singles, $world['referees'], new Collection, new MatchSides(collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$stale ? $second : $first]]])), 'Changed', tournamentVersion: $stale ? 0 : $t->refresh()->version);
    expect(fn () => resolve(UpdateMatchAction::class)->handle($match, $data))->toThrow(BaseBusinessException::class)
        ->and($match->refresh()->preview)->toBeNull()
        ->and($match->wrestlers()->count())->toBe(2);
})->with([true, false]);
it('keeps final title stakes when an ordinary match edit is valid', function (): void {
    $world = tournamentFixture(count: 2);
    $t = $world['tournament'];
    $node = $t->nodes()->where('round', 2)->firstOrFail();
    $match = bookTournamentNode($world, $node);
    $entries = $t->entries()->get();
    $title = Title::factory()->active()->for($world['promotion'])->create(['type' => TitleType::Singles]);
    $data = new EventMatchData(MatchType::Singles, $world['referees'], new Collection([$title]), new MatchSides(collect([1 => ['wrestlers' => [Wrestler::query()->findOrFail($entries->sole('seed', 1)->competitor_id)]], 2 => ['wrestlers' => [Wrestler::query()->findOrFail($entries->sole('seed', 2)->competitor_id)]]])), 'Title final', tournamentVersion: $t->refresh()->version);
    resolve(UpdateMatchAction::class)->handle($match, $data);
    expect($match->refresh()->titles()->sole()->id)->toBe($title->id);
    resolve(UpdateAction::class)->handle($world['event'], new EventData('Same date final', $world['event']->date, null, null));
    expect($world['event']->refresh()->name)->toBe('Same date final');
});
it('rechecks chronology when editing the name of a same-card event', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    foreach ($t->nodes()->get() as $node) {
        $match = bookTournamentNode($world, $node);
        recordTournamentWinner($match, $t);
    }
    resolve(UpdateAction::class)->handle($world['event'], new EventData('Tournament night', $world['event']->date, null, null));
    expect($world['event']->refresh()->name)->toBe('Tournament night')->and($t->refresh()->status)->toBe(TournamentStatus::Completed);
});

it('rejects forged low-level tournament booking contexts atomically', function (string $case): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
    $event = $world['event'];
    $entries = $t->entries()->get();
    $first = Wrestler::query()->findOrFail($entries->sole('seed', 1)->competitor_id);
    $second = Wrestler::query()->findOrFail($entries->sole('seed', 4)->competitor_id);
    $titles = new Collection;
    if ($case === 'unresolved') {
        $node = $t->nodes()->where('round', 2)->firstOrFail();
    }
    if ($case === 'withdrawn') {
        $entries->sole('seed', 1)->forceFill(['withdrawn' => true])->save();
    }
    if ($case === 'other entrants') {
        $second = Wrestler::factory()->employed()->for($world['promotion'])->create();
    }
    if ($case === 'nonfinal title') {
        $titles->push(Title::factory()->active()->for($world['promotion'])->create());
    }
    if ($case === 'foreign tournament') {
        $event = Event::factory()->for(Promotion::factory()->create())->create(['date' => now()->subHour()]);
        $first = Wrestler::factory()->employed()->for($event->promotion()->firstOrFail())->create();
        $second = Wrestler::factory()->employed()->for($event->promotion()->firstOrFail())->create();
        $world['referees'] = Referee::factory()->employed()->for($event->promotion()->firstOrFail())->count(1)->create();
    }
    if ($case === 'conflicting retry') {
        bookTournamentNode($world, $node);
        $event = Event::factory()->for($world['promotion'])->create(['date' => now()->addWeek()]);
    }
    $version = $t->refresh()->version;
    $data = new EventMatchData(MatchType::Singles, $world['referees'], $titles, new MatchSides(collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$second]]])), null);
    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $data, $node, $version))->toThrow($case === 'foreign tournament' ? RecordChangedException::class : TournamentException::class)
        ->and($event->matches()->count())->toBe(0)
        ->and($t->refresh()->version)->toBe($version);
})->with(['unresolved', 'withdrawn', 'other entrants', 'nonfinal title', 'foreign tournament', 'conflicting retry']);

it('refuses unscheduling a rematch while keeping its recorded draw history', function (): void {
    $world = tournamentFixture(repeat: false);
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $draw = bookTournamentNode($world, $node);
    recordTournamentWinner($draw, $t, MatchFinish::NoDecision);
    $later = Event::factory()->for($world['promotion'])->create(['date' => now()->addWeek()]);
    $rematch = resolve(BookTournamentMatchAction::class)->handle($node, $t->refresh()->version, new TournamentBookingData($later, $world['referees']));
    expect(fn () => resolve(UpdateAction::class)->handle($later, new EventData($later->name, null, null, null)))->toThrow(TournamentException::class)
        ->and($later->refresh()->date)->not->toBeNull()
        ->and($draw->refresh()->match_finish)->toBe(MatchFinish::NoDecision)
        ->and($node->links()->count())->toBe(2);
});
it('keeps chronology intact while a multi-event eight-slot bracket is renamed', function (): void {
    $world = tournamentFixture(capacity: 8);
    $t = $world['tournament'];
    $second = Event::factory()->for($world['promotion'])->create(['date' => now()->subMinutes(55)]);
    $semis = Event::factory()->for($world['promotion'])->create(['date' => now()->subMinutes(30)]);
    $final = Event::factory()->for($world['promotion'])->create(['date' => now()->subMinutes(15)]);
    foreach ($t->nodes()->get() as $node) {
        $event = match ($node->round) {
            1 => $node->position <= 2 ? $world['event'] : $second,2 => $semis,3 => $final,default => throw new RuntimeException('Unexpected round')
        };
        $match = resolve(BookTournamentMatchAction::class)->handle($node, $t->refresh()->version, new TournamentBookingData($event, $world['referees']));
        recordTournamentWinner($match, $t);
    }
    resolve(UpdateAction::class)->handle($world['event'], new EventData('Qualification night', $world['event']->date, null, null));
    expect($world['event']->refresh()->name)->toBe('Qualification night')->and($t->refresh()->status)->toBe(TournamentStatus::Completed);
});

it('retains a removed attempt without reusing its match number or treating it as a qualifier', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $original = bookTournamentNode($world, $node);
    resolve(ResetTournamentBranchAction::class)->handle($node, $t->refresh()->version, 'Correct booking', true);
    $replacement = bookTournamentNode($world, $node);
    resolve(UpdateAction::class)->handle($world['event'], new EventData('Revised night', $world['event']->date, null, null));
    expect($replacement->match_number)->toBe($original->match_number + 1)->and($original->refresh()->trashed())->toBeTrue()->and($node->links()->count())->toBe(2);
});

it('refuses a new draw result on a removed unplayed tournament attempt', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    resolve(ResetTournamentBranchAction::class)->handle($node, $t->refresh()->version, 'Remove unplayed booking', true);
    $version = $t->refresh()->version;
    expect(fn () => resolve(RecordResultAction::class)->handle($match, new MatchResultData(MatchFinish::NoDecision, null, collect(), $version)))->toThrow(TournamentException::class)
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and($t->refresh()->version)->toBe($version);
});
