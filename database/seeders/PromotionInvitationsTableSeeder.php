<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use Illuminate\Database\Seeder;

class PromotionInvitationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds: one pending invitation for every existing promotion.
     */
    public function run(): void
    {
        Promotion::query()->each(
            fn (Promotion $promotion): PromotionInvitation => PromotionInvitation::factory()->for($promotion)->create(),
        );
    }
}
