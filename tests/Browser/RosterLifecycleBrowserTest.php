<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\travelTo;

test('administrator can employ and retire a wrestler from the detail page', function (): void {
    // Arrange
    // The browser server runs in this process, so a fixed clock just before midnight proves the start date
    // shown after employing cannot roll over to the next day mid-test.
    travelTo(now()->setDate(2031, 7, 14)->setTime(23, 59, 30));
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $wrestler = Wrestler::factory()->unemployed()->create(['promotion_id' => $promotion->id]);
    $this->actingAs($administrator);

    // Act / Assert
    $page = visit(route('wrestlers.show', $wrestler));
    $page
        ->assertSee($wrestler->name)
        ->assertPresent('button:has-text("Employ")')
        ->assertMissing('button:has-text("Retire")')
        ->assertSeeIn('tr:has-text("Status:")', 'Unemployed')
        ->assertSeeIn('tr:has-text("Start Date:")', 'No Start Date Set')
        ->click('button:has-text("Employ")')
        ->assertSee('Wrestler has been hired.')
        ->assertSeeIn('tr:has-text("Status:")', 'Employed')
        ->assertDontSeeIn('tr:has-text("Status:")', 'Unemployed')
        ->assertSeeIn('tr:has-text("Start Date:")', now()->toDateString())
        ->assertDontSeeIn('tr:has-text("Start Date:")', 'No Start Date Set')
        ->assertPresent('button:has-text("Retire")')
        ->assertMissing('button:has-text("Employ")')
        ->click('button:has-text("Retire")')
        ->assertSee('Wrestler has been retired.')
        ->assertSeeIn('tr:has-text("Status:")', 'Retired')
        ->assertPresent('button:has-text("Unretire")')
        ->assertNoJavascriptErrors();

    expect($wrestler->refresh()->currentRetirement()->exists())->toBeTrue();
});
