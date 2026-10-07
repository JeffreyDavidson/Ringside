<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scopes matches to the enforced promotion through their event. EventMatch registers it under the
 * PromotionContextScope key so every promotion-owned model is unscoped the same way.
 *
 * @implements Scope<Model>
 */
final class EventMatchPromotionContextScope implements Scope
{
    #[\Override]
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(PromotionContextService::class);

        if ($context->failsClosed()) {
            $builder->whereRaw('0 = 1');

            return;
        }

        if (! $context->isEnforced()) {
            return;
        }

        $promotion = $context->current();

        $builder->whereHas('event', function (Builder $eventQuery) use ($promotion): void {
            if ($promotion === null) {
                $eventQuery->whereNull('promotion_id')->whereNotNull('promotion_id');

                return;
            }

            $eventQuery->where('promotion_id', $promotion->getKey());
        });
    }
}
