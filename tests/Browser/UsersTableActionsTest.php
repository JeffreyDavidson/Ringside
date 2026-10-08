<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;

test('each user row has a labelled keyboard-operable actions menu', function (): void {
    // Arrange
    User::factory()->create([
        'first_name' => 'Keyboard',
        'last_name' => 'Member',
        'status' => UserStatus::Active,
    ]);
    actingAs(administrator());
    $page = visit(route('users.index'));
    $page->resize(1440, 900);

    // Act
    $page->keys('button[aria-label="Actions for Keyboard Member"]', 'Enter');
    waitForSettledScript($page, 'document.querySelector(\'button[aria-label="Actions for Keyboard Member"]\').getAttribute("aria-expanded") === "true"');

    // Assert
    $page->assertAttribute('button[aria-label="Actions for Keyboard Member"]', 'aria-expanded', 'true')
        ->assertVisible('tr:has-text("Keyboard Member") [aria-label="User actions"]')
        ->assertScript('[...document.querySelectorAll("tr")].find((row) => row.textContent.includes("Keyboard Member")).querySelectorAll("[aria-label=\"User actions\"] li > a, [aria-label=\"User actions\"] li > button").length === 3')
        ->assertScript('[...[...document.querySelectorAll("tr")].find((row) => row.textContent.includes("Keyboard Member")).querySelectorAll("[aria-label=\"User actions\"] li > a, [aria-label=\"User actions\"] li > button")].every((control) => { control.focus(); return document.activeElement === control; })')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});
