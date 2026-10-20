<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\LiveShowChangeData;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveItemTransitions\LiveItemTransitionResolver;
use App\Models\Events\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransitionLiveItemAction
{
    public function __construct(private readonly LockLiveShowAction $lock, private readonly PersistLiveShowAction $persist,
        private readonly LiveItemTransitionResolver $transitions) {}

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
            if ($operation->requiresReason() && ($reason === null || mb_trim($reason) === '' || mb_strlen($reason) > 2000)) {
                throw LiveShowException::reasonRequired();
            }
            $updated = $this->transitions->resolve($operation)->apply($run, $index, $start, $finish);
            if ($operation === LiveItemOperation::Start) {
                $run->status = LiveShowStatus::Running;
            }
            $updated['reason'] = $reason;
            $updated['updated_by'] = (int) auth()->id();
            $content['items'][$index] = $updated;
            $run->content = $content;
            $this->persist->handle($run, new LiveShowChangeData($operation->value, $key, $before, $content['items'][$index], $reason));
        }, attempts: 3);
    }
}
