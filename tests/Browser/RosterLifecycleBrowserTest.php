<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;

test('administrator can employ and retire a wrestler from the detail page', function (): void {
    // Arrange
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
    $page->script('window.confirm = () => true');
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

test('retiring a wrestler waits for the administrator to confirm it', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $wrestler = Wrestler::factory()->bookable()->create(['name' => "Ann D'Arcy", 'promotion_id' => $promotion->id]);
    $this->actingAs($administrator);

    // Act / Assert
    $page = visit(route('wrestlers.show', $wrestler));
    $page->script('window.confirm = (message) => { window.confirmedMessage = message; return false; }');
    $page
        ->click('button:has-text("Retire")')
        ->assertScript('window.confirmedMessage', "Retire Ann D'Arcy?")
        ->assertSeeIn('tr:has-text("Status:")', 'Employed')
        ->assertPresent('button:has-text("Retire")');
    $page->script('window.confirm = () => true');
    $page
        ->click('button:has-text("Retire")')
        ->assertSee('Wrestler has been retired.')
        ->assertSeeIn('tr:has-text("Status:")', 'Retired')
        ->assertNoJavascriptErrors();

    expect($wrestler->refresh()->retirements()->count())->toBe(1);
});
