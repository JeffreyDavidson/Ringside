<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\LiveShowChangeData;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowDayRequirements;
use App\Lifecycle\Events\LiveShowLock;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Lifecycle\Events\LiveShowStateMachine;
use App\Models\Events\Event;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class CompleteLiveShowAction
{
    public function __construct(
        private readonly LiveShowLock $lock,
        private readonly PersistLiveShowAction $persist,
        private readonly LiveShowSourceRequirements $source,
        private readonly LiveShowDayRequirements $showDay,
        private readonly LiveShowStateMachine $states,
    ) {}

    public function handle(Event $event, int $version, User $actor): void
    {
        DB::transaction(function () use ($event, $version, $actor): void {
            $run = $this->lock->lock($event, $version);
            $this->showDay->ensureShowDay($run);
            $this->states->ensureCanComplete($run);

            if ($this->source->hasChanged($run->event, $run)) {
                throw LiveShowException::reviewRequired();
            }

            $before = ['status' => $run->status->value];
            $run->status = LiveShowStatus::Completed;
            $run->completed_at = now();
            $this->persist->handle($run, new LiveShowChangeData('complete', 'show', $before, ['status' => $run->status->value]), $actor);
        }, attempts: 3);
    }
}
