<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;

test('administrator can disband and retire a stable from the detail page', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $stable = Stable::factory()->active()->create(['promotion_id' => $promotion->id]);
    $this->actingAs($administrator);

    // Act / Assert
    $statusCell = 'Array.from(document.querySelectorAll("tr")).find(row => row.firstElementChild.textContent.trim() === "Status:").lastElementChild.textContent.trim()';
    $page = visit(route('stables.show', $stable));
    $page
        ->assertSee($stable->name)
        ->assertPresent('button:has-text("Disband")')
        ->assertPresent('button:has-text("Retire")')
        ->assertMissing('button:has-text("Unretire")')
        ->assertScript($statusCell, 'Active')
        ->click('button:has-text("Disband")')
        ->assertSee('Stable successfully disbanded.')
        ->assertScript($statusCell, 'Inactive')
        ->assertMissing('button:has-text("Disband")')
        ->click('button:has-text("Retire")')
        ->assertSee('Stable successfully retired.')
        ->assertScript($statusCell, 'Retired')
        ->assertMissing('button:has-text("Retire")')
        ->assertNoJavascriptErrors();

    expect($stable->refresh()->currentRetirement()->exists())->toBeTrue();
});
