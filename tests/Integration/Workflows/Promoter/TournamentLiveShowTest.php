<?php

declare(strict_types=1);
use App\Actions\Events\MarkLiveAttendanceAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveAttendanceMark;
use App\Enums\Events\LiveItemOperation;
use App\ViewModels\EventPackViewModel;

use function Pest\Laravel\actingAs;

it('adopts a newly qualified final through pack and live review while preserving completed timings and arrivals', function (): void {
    actingAs(administrator());
    $world = tournamentFixture();
    $t = $world['tournament'];
    $event = $world['event'];
    $event->update(['date' => now()]);
    $nodes = $t->nodes()->get();
    $first = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 1));
    $second = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 1 && $n->position === 2));
    $vm = resolve(EventPackViewModel::class);
    $data = $vm->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], $data['content'], false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    resolve(MarkLiveAttendanceAction::class)->handle($event, $run->version, $run->content['roster'][0]['key'], LiveAttendanceMark::Arrived, true);
    foreach ([$first, $second] as $match) {
        resolve(TransitionLiveItemAction::class)->handle($event, $run->refresh()->version, 'match:'.$match->id, LiveItemOperation::Start);
        resolve(TransitionLiveItemAction::class)->handle($event, $run->refresh()->version, 'match:'.$match->id, LiveItemOperation::Finish);
        recordTournamentWinner($match, $t);
    }
    $before = $run->refresh()->content;
    $final = bookTournamentNode($world, $nodes->sole(fn ($n): bool => $n->round === 2));
    $data = $vm->data($event);
    expect($data['needsReview'])->toBeTrue()->and($run->refresh()->content['items'])->toHaveCount(2);
    resolve(SaveEventPackAction::class)->handle($event, 1, $data['card']['fingerprint'], $data['content'], true);
    resolve(ReviewLiveShowAction::class)->handle($event, $run->refresh()->version, 2, $data['card']['fingerprint'], []);
    $after = $run->refresh()->content;
    expect($after['items'][0])->toBe($before['items'][0])->and($after['items'][1])->toBe($before['items'][1])
        ->and($after['items'][2]['key'])->toBe('match:'.$final->id)->and($after['items'][2]['state'])->toBe('pending')
        ->and($after['roster'][0]['arrived'])->toBe($before['roster'][0]['arrived']);
});
