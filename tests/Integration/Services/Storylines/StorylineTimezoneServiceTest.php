<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Services\Storylines\StorylineTimezoneService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('storyline timezone service', function (): void {
    it('dates a new account in the promotion timezone', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $story = Storyline::factory()->for($promotion)->create();

        // Act
        $zone = resolve(StorylineTimezoneService::class)->forDevelopment($story, null);

        // Assert
        expect($zone)->toBe('America/New_York');
    });

    it('keeps the timezone a correction was recorded in after the promotion changes its own', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $story = Storyline::factory()->for($promotion)->create();
        $development = StorylineDevelopment::factory()->for($story)->create(['timezone' => 'America/New_York']);
        $promotion->timezone = 'UTC';
        $promotion->save();

        // Act
        $zone = resolve(StorylineTimezoneService::class)->forDevelopment($story, $development->id);

        // Assert
        expect($zone)->toBe('America/New_York');
    });

    it('does not find a development of another storyline', function (): void {
        // Arrange
        $story = Storyline::factory()->create();
        $other = StorylineDevelopment::factory()->create();

        // Act
        $zone = fn () => resolve(StorylineTimezoneService::class)->forDevelopment($story, $other->id);

        // Assert
        expect($zone)->toThrow(ModelNotFoundException::class);
    });
});
