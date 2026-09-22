<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPromotion
{
    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
