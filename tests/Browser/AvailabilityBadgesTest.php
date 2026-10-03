<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;

test('availability badges keep readable contrast on the dark theme', function (string $state, string $badge): void {
    // Arrange
    $wrestler = Wrestler::factory()->{$state}()->create(['name' => 'Badge Contrast']);
    $this->actingAs(administrator());

    // Act
    $page = visit(route('wrestlers.show', $wrestler));
    $page->resize(1440, 900);

    // Assert
    $page->assertVisible("[data-test={$badge}]")
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
})->with([
    'injured' => ['injured', 'availability-injured'],
    'suspended' => ['suspended', 'availability-suspended'],
]);
