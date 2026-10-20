<?php

declare(strict_types=1);
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\PlanTournamentBoutAction;
use App\Data\Tournaments\TournamentBookingData;
use App\Enums\Titles\TitleType;
use App\Enums\Tournaments\TournamentStatus;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * @param  list<array{operation:string,node_id:int,event_id:int,referee_id:int,version:int,match_id:int,side_id:int}>  $specs
 * @return list<array{ok:bool,deadlock:bool}>
 */
function tournamentRace(array $specs): array
{
    $processes = [];
    $inputs = [];
    foreach ($specs as $index => $spec) {
        $inputs[$index] = new InputStream;
        $processes[$index] = new Process([PHP_BINARY, __DIR__.'/tournament-worker.php', json_encode($spec, JSON_THROW_ON_ERROR)]);
        $processes[$index]->setTimeout(60);
        $processes[$index]->setInput($inputs[$index]);
        $processes[$index]->start();
    }
    $deadline = microtime(true) + 15;
    foreach ($processes as $process) {
        while (! str_contains($process->getOutput(), 'READY') && $process->isRunning() && microtime(true) < $deadline) {
            usleep(10000);
        }
        if (! str_contains($process->getOutput(), 'READY')) {
            throw new RuntimeException('Tournament worker failed to initialize: '.$process->getErrorOutput());
        }
    }
    $start = microtime(true) + 0.2;
    foreach ($inputs as $input) {
        $input->write($start."\n");
        $input->close();
    }
    while (microtime(true) < $start) {
        foreach ($processes as $process) {
            $process->isRunning();
        } usleep(1000);
    }
    $outcomes = [];
    foreach ($processes as $process) {
        $process->wait();
        preg_match('/RESULT:(.*)/', $process->getOutput(), $matches);
        $outcome = json_decode($matches[1] ?? '', true);
        if (! is_array($outcome) || ! isset($outcome['ok'], $outcome['deadlock']) || ! is_bool($outcome['ok']) || ! is_bool($outcome['deadlock'])) {
            throw new RuntimeException('Tournament worker failed: '.$process->getErrorOutput());
        }
        $outcomes[] = ['ok' => $outcome['ok'], 'deadlock' => $outcome['deadlock']];
    }

    return $outcomes;
}
/** @param array{tournament:Tournament,promotion:Promotion,event:Event,referees:Collection<int,Referee>} $world
 * @return array{operation:string,node_id:int,event_id:int,referee_id:int,version:int,match_id:int,side_id:int}
 */
function tournamentWorkerSpec(array $world): array
{
    return ['operation' => 'book', 'node_id' => $world['tournament']->nodes()->firstOrFail()->id, 'event_id' => $world['event']->id, 'referee_id' => $world['referees']->sole()->id, 'version' => $world['tournament']->refresh()->version, 'match_id' => 0, 'side_id' => 0];
}
it('serializes duplicate node bookings without duplicate matches or deadlocks', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $spec = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([$spec, $spec]);
        expect(EventMatch::query()->where('event_id', $world['event']->id)->count())->toBe(1)
            ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
            ->and(deadlocksResolvedSince($before))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
it('preserves two sibling qualifications recorded by real concurrent processes', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $nodes = $world['tournament']->nodes()->where('round', 1)->get();
        $first = bookTournamentNode($world, $nodes->sole('position', 1));
        $second = bookTournamentNode($world, $nodes->sole('position', 2));
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([[...$base, 'operation' => 'result', 'match_id' => $first->id, 'side_id' => $first->sides()->firstOrFail()->id], [...$base, 'operation' => 'result', 'match_id' => $second->id, 'side_id' => $second->sides()->firstOrFail()->id]]);
        expect(collect($results)->where('ok', true)->count())->toBe(2)
            ->and(resolve(TournamentBracketService::class)->bouts($world['tournament'])->last()?->inputsResolved)->toBeTrue()
            ->and(deadlocksResolvedSince($before))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
it('refuses a stale opposing outcome from a concurrent recorder', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $match = bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
        $base = tournamentWorkerSpec($world);
        $sides = $match->sides()->get();
        $before = resolvedDeadlocks();
        $results = tournamentRace([[...$base, 'operation' => 'result', 'match_id' => $match->id, 'side_id' => $sides->sole('position', 1)->id], [...$base, 'operation' => 'result', 'match_id' => $match->id, 'side_id' => $sides->sole('position', 2)->id]]);
        expect(collect($results)->where('ok', true)->count())->toBe(1)
            ->and($match->refresh()->winning_side_id)->not->toBeNull()
            ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
            ->and(deadlocksResolvedSince($before))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

it('serializes a qualifier correction against a dependent booking', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $t = $world['tournament'];
        $nodes = $t->nodes()->get();
        $first = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1));
        recordTournamentWinner($first, $t);
        $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
        recordTournamentWinner($second, $t);
        $final = $nodes->sole(fn ($n): bool => $n->round === 2);
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([[...$base, 'node_id' => $final->id], [...$base, 'operation' => 'result', 'match_id' => $first->id, 'side_id' => $first->sides()->where('position', 2)->firstOrFail()->id]]);
        expect(collect($results)->where('ok', true)->count())->toBe(1)->and(deadlocksResolvedSince($before))->toBe(0);
        $bout = resolve(TournamentBracketService::class)->bout($final);
        if ($bout->link !== null) {
            expect($bout->link->match->wrestlers()->pluck('wrestlers.id')->all())->toEqualCanonicalizing([$bout->first?->competitor_id, $bout->second?->competitor_id]);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
it('keeps the same-card exemption away from an ordinary competing assignment', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $t = $world['tournament'];
        $nodes = $t->nodes()->get();
        $first = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1));
        recordTournamentWinner($first, $t);
        $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
        recordTournamentWinner($second, $t);
        $ordinary = EventMatch::factory()->for($world['event'])->create(['match_finish' => null]);
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([[...$base, 'node_id' => $nodes->sole(fn ($n): bool => $n->round === 2)->id], [...$base, 'operation' => 'ordinary', 'match_id' => $ordinary->id, 'side_id' => $first->winningSide?->competitors()->firstOrFail()->competitor_id ?? throw new RuntimeException('Expected winner')]]);
        expect(collect($results)->where('ok', true)->count())->toBe(1)->and($ordinary->wrestlers()->count())->toBe(0)->and(deadlocksResolvedSince($before))->toBe(0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
it('queues tournament booking with a reschedule of its reviewed future event', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $t = $world['tournament'];
        $world['event']->update(['date' => now()->addWeek()]);
        $node = $t->nodes()->firstOrFail();
        resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], $t->version);
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $original = $world['event']->date ?? throw new RuntimeException('Expected scheduled date');
        $results = tournamentRace([$base, [...$base, 'operation' => 'reschedule']]);
        expect(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()->and(deadlocksResolvedSince($before))->toBe(0)
            ->and($world['event']->refresh()->date?->equalTo($original->copy()->addHours(2)))->toBeTrue()
            ->and($node->links()->count())->toBeLessThanOrEqual(1);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
it('rechecks employment when release races a first-round booking', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $t = $world['tournament'];
        $world['event']->update(['date' => now()->addWeek()]);
        $node = $t->nodes()->firstOrFail();
        $entry = $t->entries()->firstOrFail();
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([$base, [...$base, 'operation' => 'release', 'side_id' => $entry->competitor_id]]);
        expect(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()->and(deadlocksResolvedSince($before))->toBe(0);
        $booked = collect($results)->first();
        $released = collect($results)->last();
        expect($released['ok'] ?? false)->toBeTrue()
            ->and($node->links()->count())->toBe(($booked['ok'] ?? false) ? 1 : 0);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

it('serializes an explicit branch reset against a dependent booking', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture();
        $t = $world['tournament'];
        $nodes = $t->nodes()->get();
        $source = $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1);
        $first = bookTournamentNode($world, $source);
        recordTournamentWinner($first, $t);
        $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
        recordTournamentWinner($second, $t);
        $final = $nodes->sole(fn ($n): bool => $n->round === 2);
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([[...$base, 'node_id' => $final->id], [...$base, 'operation' => 'reset', 'node_id' => $source->id]]);
        expect($results[0]['ok'])->toBeTrue()->and(deadlocksResolvedSince($before))->toBe(0)
            ->and($final->links()->whereNotNull('active_node_id')->count())->toBe(1)
            ->and($t->refresh()->version)->toBe($base['version'] + 1);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
it('commits a final result and its championship lineage atomically under competing recorders', function (): void {
    withCommittedData(function (): void {
        $world = tournamentFixture(count: 2);
        $t = $world['tournament'];
        $final = $t->nodes()->where('round', 2)->firstOrFail();
        $title = Title::factory()->active()->for($world['promotion'])->create(['type' => TitleType::Singles]);
        $match = resolve(BookTournamentMatchAction::class)->handle($final, $t->version, new TournamentBookingData($world['event'], $world['referees'], new Collection([$title])));
        $base = tournamentWorkerSpec($world);
        $before = resolvedDeadlocks();
        $results = tournamentRace([[...$base, 'operation' => 'result', 'match_id' => $match->id, 'side_id' => $match->sides()->where('position', 1)->firstOrFail()->id], [...$base, 'operation' => 'result', 'match_id' => $match->id, 'side_id' => $match->sides()->where('position', 2)->firstOrFail()->id]]);
        expect(collect($results)->where('ok', true)->count())->toBe(1)->and(deadlocksResolvedSince($before))->toBe(0)
            ->and($t->refresh()->status)->toBe(TournamentStatus::Completed)
            ->and($title->championships()->current()->sole()->champion->id)->toBe($match->refresh()->winningSide?->competitors()->firstOrFail()->competitor_id);
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
