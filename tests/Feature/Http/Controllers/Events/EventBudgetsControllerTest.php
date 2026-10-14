<?php

declare(strict_types=1);

use App\Actions\Events\SaveBudgetLineAction;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the budget page opens without recording a budget', function (): void {
    // Arrange
    $event = Event::factory()->create();

    actingAs(administrator());

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.budget')
        ->assertViewHas('event', $event)
        ->assertSeeHtml('Budget &amp; settlement')
        ->assertSee('No budget recorded');

    expect(EventBudget::query()->count())->toBe(0);
});

require_once __DIR__.'/../../../../Fixtures/budget.php';

test('only owners managers and permitted administrators see finances', function (string $role, int $status): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();

    $user = $role === 'administrator' ? administrator() : basicUser();
    if ($role !== 'administrator') {
        $promotion->users()->attach($user, [
            'role' => $role,
            'status' => 'active',
        ]);
    }

    actingAs($user);

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response->assertStatus($status);

    expect(EventBudget::query()->count())->toBe(0);
})->with([
    'owner' => ['owner', 200],
    'manager' => ['manager', 200],
    'member' => ['member', 403],
    'administrator' => ['administrator', 200],
]);

test('guests and foreign promotion ids cannot open budgets', function (): void {
    // Arrange
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response->assertRedirect(route('login'));

    // Arrange
    $promotion = Promotion::factory()->create();

    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => 'owner',
        'status' => 'active',
    ]);

    actingAs($user);

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response->assertNotFound();
});

test('financial markers are absent from event pack print and live show payloads for a member', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();

    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput([
        'confirmed' => '8149.37',
        'notes' => 'PRIVATE_FINANCIAL_NOTE_937',
        'counterparty' => 'PRIVATE_PAYEE_937',
    ]));
    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => 'member',
        'status' => 'active',
    ]);

    actingAs($user);

    // Act
    foreach (['events.show', 'events.pack.show', 'events.live-show.show'] as $name) {
        $response = get(route($name, $event));
        $response
            ->assertOk()
            ->assertViewIs(match ($name) {
                'events.show' => 'events.show', 'events.pack.show' => 'events.pack', 'events.live-show.show' => 'events.live'
            })
            ->assertViewHas('event', $event)
            ->assertDontSee('8149.37')
            ->assertDontSee('814937')
            ->assertDontSee('PRIVATE_FINANCIAL_NOTE_937')
            ->assertDontSee('PRIVATE_PAYEE_937')
            ->assertDontSeeHtml('Budget &amp; settlement');
    }
    $response = get(route('events.pack.show', [
        'event' => $event,
        'print' => 1,
    ]));

    // Assert
    $response
        ->assertOk()
        ->assertDontSee('PRIVATE_FINANCIAL_NOTE_937')
        ->assertDontSee('814937');

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response->assertForbidden();
});

test('a dated promotion event shows the local show date on its financial page', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion, 'promotion')->create(['date' => now()]);

    actingAs(administrator());

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.budget')
        ->assertViewHas('event', $event)
        ->assertSee(now('America/New_York')->format('M j, Y'));
});

test('outsiders and view-only members cannot change any business records by opening finance', function (string $role): void {
    // Arrange
    $victim = Promotion::factory()->create();
    $event = Event::factory()->for($victim, 'promotion')->create();

    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['notes' => 'LEAK-BUDGET-7Q']));
    $user = basicUser();
    $promotion = $role === 'outsider' ? Promotion::factory()->create() : $victim;
    $promotion->users()->attach($user, [
        'role' => $role === 'outsider' ? 'owner' : 'member',
        'status' => 'active',
    ]);

    actingAs($user);
    $before = budgetBusinessFingerprint();

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response->assertStatus($role === 'outsider' ? 404 : 403);

    expect(budgetBusinessFingerprint())->toBe($before);
})->with([
    'outsider' => 'outsider',
    'member' => 'member',
]);

test('owners can read the financial markers while every ordinary and shared output omits them', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();

    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput([
        'confirmed' => '8149.37',
        'notes' => 'LEAK-BUDGET-NOTE-7Q',
        'counterparty' => 'LEAK-BUDGET-PAYEE-7Q',
    ]));
    $owner = basicUser();
    $promotion->users()->attach($owner, [
        'role' => 'owner',
        'status' => 'active',
    ]);

    actingAs($owner);
    $before = budgetBusinessFingerprint();

    // Act: positive control proves the fields really exist and the owner's page is reachable.
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('LEAK-BUDGET-NOTE-7Q')
        ->assertSee('8149.37 USD');

    expect(budgetBusinessFingerprint())->toBe($before);
    $leaks = [];
    foreach (budgetSharedSurfaces($event) as $name => $text) {
        foreach (['LEAK-BUDGET-NOTE-7Q', 'LEAK-BUDGET-PAYEE-7Q', '8149.37', '814937', '8,149.37'] as $marker) {
            if (str_contains($text, $marker)) {
                $leaks[] = "{$name}: {$marker}";
            }
        }
    }
    expect($leaks)->toBeEmpty();
});
