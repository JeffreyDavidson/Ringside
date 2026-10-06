<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use Database\Seeders\PromotionInvitationsTableSeeder;

use function Pest\Laravel\seed;

test('it seeds one pending invitation for every promotion', function () {
    // Arrange
    $promotions = Promotion::factory()->count(2)->create();

    // Act
    seed(PromotionInvitationsTableSeeder::class);

    // Assert
    expect(PromotionInvitation::query()->orderBy('promotion_id')->pluck('promotion_id')->all())
        ->toBe($promotions->modelKeys());
});
