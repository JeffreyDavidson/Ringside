<?php

declare(strict_types=1);

use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Support\Carbon;

use function Pest\Laravel\travelTo;

test('dates transitions from the application clock rather than the real one', function () {
    // Arrange
    travelTo(Carbon::parse('2030-06-01 12:00:00'));

    // Act
    $transition = LifecycleTransition::factory()->make();

    // Assert
    expect($transition->effective_at->between(now()->subYear()->subDay(), now()))->toBeTrue();
});
