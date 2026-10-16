<?php

declare(strict_types=1);

use App\Actions\Events\SaveBudgetLineAction;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Promotions\Promotion;
use Illuminate\View\View;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

require_once __DIR__.'/../../../../Fixtures/budget.php';

test('the budget page opens without recording a budget', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

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

    expect(EventBudget::query()
        ->count())->toBe(0);
});

test('a dated promotion event shows the local show date on its financial page', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create(['timezone' => 'America/New_York']);
    $event = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()]);

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

test('owners and managers can read event finances', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.budget')
        ->assertViewHas('event', $event);

    expect(EventBudget::query()
        ->count())->toBe(0);
})->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

test('a global administrator can read finances without membership', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAs(administrator());

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.budget')
        ->assertViewHas('event', $event);

    expect(EventBudget::query()
        ->count())->toBe(0);
});

test('active members cannot read event finances', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertForbidden();

    expect(EventBudget::query()
        ->count())->toBe(0);
});

test('guests cannot open budgets', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('foreign event identifiers cannot open budgets', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $other = Promotion::factory()
        ->create();
    actingAsPromotionMember($other, MembershipRole::Owner);

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertNotFound();
});

test('member event surfaces omit every financial marker and finance link', function (string $routeName, bool $print, string $view): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['confirmed' => '8149.37', 'notes' => 'PRIVATE_FINANCIAL_NOTE_937', 'counterparty' => 'PRIVATE_PAYEE_937']));

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route($routeName, ['event' => $event, 'print' => $print]));

    // Assert
    $response
        ->assertOk()
        ->assertViewHas('event', $event)
        ->assertDontSee('8149.37')
        ->assertDontSee('814937')
        ->assertDontSee('PRIVATE_FINANCIAL_NOTE_937')
        ->assertDontSee('PRIVATE_PAYEE_937')
        ->assertDontSeeHtml('Budget &amp;

    Assert::assertInstanceOf(View::class, $response->original);
    expect($response->original->name())->toBe($view); settlement');
})->with(['event' => ['events.show', false, 'events.show'], 'pack' => ['events.pack.show', false, 'events.pack'], 'print' => ['events.pack.show', true, 'events.pack-print'], 'live' => ['events.live-show.show', false, 'events.live']]);

test('outsiders cannot change any business records by opening finance', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['confirmed' => '8149.37', 'notes' => 'PRIVATE_FINANCIAL_NOTE_937', 'counterparty' => 'PRIVATE_PAYEE_937']));
    $other = Promotion::factory()
        ->create();
    actingAsPromotionMember($other, MembershipRole::Owner);
    $before = budgetBusinessFingerprint();

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertNotFound();

    expect(budgetBusinessFingerprint())->toBe($before);
});

test('read-only members cannot change any business records by opening finance', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['confirmed' => '8149.37', 'notes' => 'PRIVATE_FINANCIAL_NOTE_937', 'counterparty' => 'PRIVATE_PAYEE_937']));
    actingAsPromotionMember($promotion, MembershipRole::Member);
    $before = budgetBusinessFingerprint();

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertForbidden();

    expect(budgetBusinessFingerprint())->toBe($before);
});

test('owners can read financial markers without mutating business records', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['confirmed' => '8149.37', 'notes' => 'LEAK-BUDGET-NOTE-7Q', 'counterparty' => 'LEAK-BUDGET-PAYEE-7Q']));

    actingAsPromotionMember($promotion, MembershipRole::Owner);
    $before = budgetBusinessFingerprint();

    // Act
    $response = get(route('events.budget.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.budget')
        ->assertViewHas('event', $event)
        ->assertSee('LEAK-BUDGET-NOTE-7Q')
        ->assertSee('8149.37 USD');

    expect(budgetBusinessFingerprint())->toBe($before);
});

test('ordinary owner event outputs omit all private financial markers', function (string $routeName, bool $print, string $view): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['confirmed' => '8149.37', 'notes' => 'LEAK-BUDGET-NOTE-7Q', 'counterparty' => 'LEAK-BUDGET-PAYEE-7Q']));

    actingAsPromotionMember($promotion, MembershipRole::Owner);

    // Act
    $response = get(route($routeName, ['event' => $event, 'print' => $print]));

    // Assert
    $response
        ->assertOk()
        ->assertViewHas('event', $event);

    Assert::assertInstanceOf(View::class, $response->original);
    expect($response->original->name())->toBe($view);

    $html = $response->getContent();
    Assert::assertIsString($html);
    $html = html_entity_decode($html, ENT_QUOTES);
    // Integrity and CSRF tokens are random technical values, not financial data.
    $html = preg_replace('/("(?:checksum|csrf|_token)"\s*:\s*")[^"]*"/', '$1[technical]"', $html) ?? $html;
    $html = preg_replace('/((?:name="_token"[^>]*value|name="csrf-token"[^>]*content)=")[^"]*"/', '$1[technical]"', $html) ?? $html;
    expect($html)->not->toContain('LEAK-BUDGET-NOTE-7Q', 'LEAK-BUDGET-PAYEE-7Q', '8149.37', '814937', '8,149.37');
})->with(['event' => ['events.show', false, 'events.show'], 'pack' => ['events.pack.show', false, 'events.pack'], 'print' => ['events.pack.show', true, 'events.pack-print'], 'live' => ['events.live-show.show', false, 'events.live']]);

test('the public homepage cannot expose event finances', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    actingAs(administrator());
    resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput(['confirmed' => '8149.37', 'notes' => 'LEAK-BUDGET-NOTE-7Q', 'counterparty' => 'LEAK-BUDGET-PAYEE-7Q']));

    actingAsPromotionMember($promotion, MembershipRole::Owner);
    auth()->logout();

    // Act
    $response = get(route('home'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('marketing');

    $html = $response->getContent();
    Assert::assertIsString($html);
    $html = html_entity_decode($html, ENT_QUOTES);
    // Integrity and CSRF tokens are random technical values, not financial data.
    $html = preg_replace('/("(?:checksum|csrf|_token)"\s*:\s*")[^"]*"/', '$1[technical]"', $html) ?? $html;
    $html = preg_replace('/((?:name="_token"[^>]*value|name="csrf-token"[^>]*content)=")[^"]*"/', '$1[technical]"', $html) ?? $html;
    expect($html)->not->toContain('LEAK-BUDGET-NOTE-7Q', 'LEAK-BUDGET-PAYEE-7Q', '8149.37', '814937', '8,149.37');
});
