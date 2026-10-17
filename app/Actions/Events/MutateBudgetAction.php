<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetRevision;
use App\Models\Users\User;
use App\Services\Promotions\PromotionTimeZoneService;
use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

/** The shared transaction, retry identity and retained history boundary for budget writes. */
class MutateBudgetAction
{
    /**
     * @param  array<string, mixed>  $payload  The internal operation fingerprint hashed into event_budget_revisions.request_hash for retry identity, not caller input; retyping it would change stored hashes.
     * @param  Closure(EventBudget): ?array{target: int, before: array<string, mixed>, after: array<string, mixed>}  $change
     */
    public function handle(Event $event, int $version, string $requestKey, BudgetOperation $operation, array $payload, Closure $change, ?string $reason = null): EventBudget
    {
        $requestKey = mb_strtolower($requestKey);
        $user = auth()->user();
        if (! $user instanceof User) {
            throw new LogicException('A budget write requires an actor.');
        }

        return DB::transaction(function () use ($event, $version, $requestKey, $operation, $payload, $change, $reason, $user): EventBudget {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $event->load('promotion');
            $budget = EventBudget::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $hash = hash('sha256', json_encode([$operation->value, $payload, $reason], JSON_THROW_ON_ERROR));
            $prior = $budget?->revisions()->where('request_key', $requestKey)->first();
            if ($prior !== null) {
                if (! hash_equals($prior->request_hash, $hash)) {
                    throw BudgetException::keyConflict();
                }

                return $budget;
            }
            if (($budget->version ?? 0) !== $version) {
                throw RecordChangedException::changed();
            }
            if ($budget === null) {
                $budget = new EventBudget;
                $budget->event_id = $event->id;
                $budget->currency = 'USD';
                $budget->timezone = PromotionTimeZoneService::zoneOf($event->promotion);
                $budget->version = 0;
                $budget->save();
            }
            $result = $change($budget);
            if ($result === null) {
                return $budget;
            }
            $budget->version++;
            $budget->save();
            $revision = new EventBudgetRevision;
            $revision->event_budget_id = $budget->id;
            $revision->version = $budget->version;
            $revision->request_key = $requestKey;
            $revision->request_hash = $hash;
            $revision->operation = $operation;
            $revision->target_id = $result['target'];
            $revision->before = $result['before'];
            $revision->after = $result['after'];
            $revision->reason = $reason;
            $revision->actor_id = $user->id;
            $revision->actor_label = "{$user->first_name} {$user->last_name}";
            $revision->save();

            return $budget;
        }, attempts: 3);
    }
}
