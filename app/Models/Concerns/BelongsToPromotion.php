<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Promotions\Promotion;
use App\Models\Scopes\PromotionContextScope;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPromotion
{
    /** Queries are scoped to the enforced promotion by PromotionContextScope. */
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

        static::addGlobalScope(new PromotionContextScope);
    }

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
