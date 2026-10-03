<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

beforeEach(function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);
});

test('the wrestler form opens with the first field focused', function (): void {
    // Act / Assert
    visit(route('wrestlers.index'))
        ->click('Add Wrestler')
        ->assertVisible('input[name="form.name"]')
        ->wait(0.5)
        ->assertScript('document.activeElement.id', 'form.name')
        ->assertNoJavascriptErrors();
});

test('clearing the wrestler form asks before discarding typed values', function (): void {
    // Arrange
    $page = visit(route('wrestlers.index'));
    $page->script('void (window.confirmCount = 0, window.confirm = () => { window.confirmCount++; return false; })');

    // Act / Assert
    $page
        ->click('Add Wrestler')
        ->assertVisible('input[name="form.name"]')
        ->click('[data-form-footer] button:has-text("Clear")')
        ->assertScript('window.confirmCount', 0)
        ->fill('input[name="form.name"]', 'Typed Name')
        ->click('[data-form-footer] button:has-text("Clear")')
        ->assertScript('window.confirmCount', 1)
        ->assertValue('input[name="form.name"]', 'Typed Name');
    $page->script('void (window.confirm = () => { window.confirmCount++; return true; })');
    $page
        ->click('[data-form-footer] button:has-text("Clear")')
        ->assertScript('window.confirmCount', 2)
        ->assertValue('input[name="form.name"]', '')
        ->assertNoJavascriptErrors();
});
