<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\LiveShowChangeData;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowDayRequirements;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Models\Events\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransitionLiveItemAction
{
    public function __construct(private readonly LockLiveShowAction $lock, private readonly PersistLiveShowAction $persist,
        private readonly LiveShowSourceRequirements $source, private readonly LiveShowDayRequirements $showDay) {}

    public function handle(Event $event, int $version, string $key, LiveItemOperation $operation, ?string $reason = null, ?Carbon $start = null, ?Carbon $finish = null): void
    {
        DB::transaction(function () use ($event, $version, $key, $operation, $reason, $start, $finish): void {
            $run = $this->lock->handle($event, $version);
            $content = $run->content;
            $index = array_search($key, array_column($content['items'], 'key'), true);
            if (! is_int($index)) {
                throw LiveShowException::unknownItem();
            }
            $before = $content['items'][$index];
            $updated = $before;
            $state = LiveItemStatus::from($before['state']);
            $running = collect($content['items'])->contains('state', LiveItemStatus::Running->value);
            if (in_array($operation, [LiveItemOperation::Skip, LiveItemOperation::Correct, LiveItemOperation::Reopen], true)
                && ($reason === null || mb_trim($reason) === '' || mb_strlen($reason) > 2000)) {
                throw LiveShowException::reasonRequired();
            }
            if ($operation === LiveItemOperation::Start) {
                if ($state !== LiveItemStatus::Pending || $running || (collect($content['items'])->firstWhere('state', 'pending')['key'] ?? null) !== $key) {
                    throw LiveShowException::transitionInvalid();
                }
                if ($this->source->hasChanged($run->event, $run)) {
                    throw LiveShowException::reviewRequired();
                }
                $this->showDay->ensureShowDay($run);
                $updated['state'] = LiveItemStatus::Running->value;
                $updated['start'] = now()->utc()->toIso8601String();
                $run->status = LiveShowStatus::Running;
            }
            if ($operation === LiveItemOperation::Finish) {
                if ($state !== LiveItemStatus::Running || $before['start'] === null || Carbon::parse($before['start'])->gt(now())) {
                    throw LiveShowException::transitionInvalid();
                }
                $updated['state'] = LiveItemStatus::Finished->value;
                $updated['finish'] = now()->utc()->toIso8601String();
            }
            if ($operation === LiveItemOperation::Skip) {
                if ($state !== LiveItemStatus::Pending) {
                    throw LiveShowException::transitionInvalid();
                }
                $this->showDay->ensureShowDay($run);
                $updated['state'] = LiveItemStatus::Skipped->value;
            }
            if ($operation === LiveItemOperation::Correct) {
                if (! in_array($state, [LiveItemStatus::Running, LiveItemStatus::Finished], true) || ! $start instanceof Carbon
                    || $start->gt(now()) || ($state === LiveItemStatus::Finished && ! $finish instanceof Carbon)
                    || ($state === LiveItemStatus::Running && $finish instanceof Carbon)
                    || ($finish instanceof Carbon && ($finish->lt($start) || $finish->gt(now())))) {
                    throw LiveShowException::invalidTimes();
                }
                foreach ($content['items'] as $position => $item) {
                    if (($position < $index && $item['finish'] !== null && Carbon::parse($item['finish'])->gt($start))
                        || ($position > $index && $item['start'] !== null && (! $finish instanceof Carbon || Carbon::parse($item['start'])->lt($finish)))) {
                        throw LiveShowException::invalidTimes();
                    }
                }
                $updated['start'] = $start->copy()->utc()->toIso8601String();
                $updated['finish'] = $finish?->copy()->utc()->toIso8601String();
            }
            if ($operation === LiveItemOperation::Reopen) {
                if (! in_array($state, [LiveItemStatus::Finished, LiveItemStatus::Skipped], true) || $running
                    || collect(array_slice($content['items'], $index + 1))->contains(fn (array $item): bool => $item['start'] !== null)) {
                    throw LiveShowException::transitionInvalid();
                }
                $updated['state'] = $state === LiveItemStatus::Finished ? LiveItemStatus::Running->value : LiveItemStatus::Pending->value;
                $updated['finish'] = null;
            }
            $updated['reason'] = $reason;
            $updated['updated_by'] = (int) auth()->id();
            $content['items'][$index] = $updated;
            $run->content = $content;
            $this->persist->handle($run, new LiveShowChangeData($operation->value, $key, $before, $content['items'][$index], $reason));
        }, attempts: 3);
    }
}
