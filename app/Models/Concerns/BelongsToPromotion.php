<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPromotion
{
    protected static function bootBelongsToPromotion(): void
    {
        static::creating(function (Model $model): void {
            $context = app(PromotionContextService::class);

            if (! $context->isEnforced() || $model->getAttribute('promotion_id') !== null) {
                return;
            }

            $model->forceFill([
                'promotion_id' => $context->required()->getKey(),
            ]);
        });

        static::addGlobalScope('promotion_context', function (Builder $builder): void {
            $context = app(PromotionContextService::class);

            if (! $context->isEnforced()) {
                return;
            }

            $promotion = $context->current();
            $column = $builder->getModel()->qualifyColumn('promotion_id');

            if ($promotion === null) {
                $builder->whereNull($column)->whereNotNull($column);

                return;
            }

            $builder->where($column, $promotion->getKey());
        });
    }

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
