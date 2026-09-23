<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Data\Promotions\PromotionData;
use App\Models\Promotions\Promotion;

class CreateAction
{
    public function handle(PromotionData $data): Promotion
    {
        return Promotion::query()->create([
            'name' => $data->name,
            'slug' => $data->slug,
        ]);
    }
}
