<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

test('an administrator removes a wrestler, finds it under Deleted and restores it', function (): void {
    // Arrange
    Wrestler::factory()->create(['name' => 'Browser Wrestler']);
    actingAs(administrator());

    // Act / Assert
    $page = visit(route('wrestlers.index'));
    $page->script('void (window.confirm = () => true)');
    $page
        ->resize(1440, 900)
        ->assertSee('Browser Wrestler')
        ->click('tr:has-text("Browser Wrestler") button[x-ref="button"]')
        ->click('tr:has-text("Browser Wrestler") button:has-text("Remove")')
        ->assertSee('Wrestler successfully deleted.')
        ->assertDontSee('Browser Wrestler')
        ->click('[data-test="roster-status-filters"] button:has-text("Deleted")')
        ->assertSee('Browser Wrestler')
        ->click('tr:has-text("Browser Wrestler") button[x-ref="button"]')
        ->assertMissing('tr:has-text("Browser Wrestler") button:has-text("Remove")')
        ->click('tr:has-text("Browser Wrestler") button:has-text("Restore")')
        ->assertSee('Wrestler has been restored.')
        ->assertDontSee('Browser Wrestler')
        ->click('[data-test="roster-status-filters"] button:has-text("All")')
        ->assertSee('Browser Wrestler')
        ->assertNoJavascriptErrors();

    expect(Wrestler::query()->where('name', 'Browser Wrestler')->exists())->toBeTrue();
});
