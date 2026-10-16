<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\Event;
use App\Queries\Events\LiveShowSourceQuery;
use App\Services\Events\LiveShowTimingService;
use Illuminate\Support\Facades\DB;

class CompleteLiveShowAction
{
    public function __construct(private readonly LockLiveShowAction $lock, private readonly PersistLiveShowAction $persist,
        private readonly LiveShowSourceQuery $source, private readonly LiveShowTimingService $timing) {}

    public function handle(Event $event, int $version): void
    {
        DB::transaction(function () use ($event, $version): void {
            $run = $this->lock->handle($event, $version);
            $this->timing->ensureShowDay($run);
            if (collect($run->content['items'])->contains(fn (array $item): bool => in_array($item['state'], ['pending', 'running'], true))) {
                throw LiveShowException::unfinished();
            }
            if ($this->source->changed($run->event, $run)) {
                throw LiveShowException::reviewRequired();
            }
            $before = ['status' => $run->status->value];
            $run->status = LiveShowStatus::Completed;
            $run->completed_at = now();
            $this->persist->handle($run, 'complete', 'show', $before, ['status' => $run->status->value]);
        }, attempts: 3);
    }
}
