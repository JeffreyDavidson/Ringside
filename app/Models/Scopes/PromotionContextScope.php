<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scopes every query to the enforced promotion. When no promotion is enforced, queries are
 * unscoped for administrators, console, queue and guest contexts, but match nothing for an
 * authenticated non-administrator (see PromotionContextService::failsClosed()).
 *
 * @implements Scope<Model>
 */
final class PromotionContextScope implements Scope
{
    #[\Override]
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(PromotionContextService::class);
        $column = $model->qualifyColumn('promotion_id');

        if ($context->failsClosed()) {
            $builder->whereNull($column)->whereNotNull($column);

            return;
        }

        if (! $context->isEnforced()) {
            return;
        }

        $promotion = $context->current();

        if ($promotion === null) {
            $builder->whereNull($column)->whereNotNull($column);

            return;
        }

        $builder->where($column, $promotion->getKey());
    }
}
