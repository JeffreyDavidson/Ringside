<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Data\Promotions\PromotionData;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function handle(Promotion $promotion, PromotionData $data): Promotion
    {
        return DB::transaction(function () use ($promotion, $data): Promotion {
            $lockedPromotion = $promotion->refreshForUpdate();
            $lockedPromotion->update([
                'name' => $data->name,
                'slug' => $data->slug,
            ]);

            return $lockedPromotion;
        });
    }
}
