<?php

declare(strict_types=1);
use App\Actions\Events\RecordBudgetMovementAction;
use App\Actions\Events\SaveBudgetLineAction;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Events\EventBudgetMovement;
use App\Models\Events\EventBudgetRevision;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\ViewModels\EventBudgetViewModel;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

require_once __DIR__.'/../../Fixtures/budget.php';

test('ticket refunds show net confirmed income and cash without crossing counterparties', function (): void {
    // Arrange
    $f = budgetFixture(['direction' => 'income', 'category' => 'tickets', 'planned_cents' => 200000, 'confirmed_cents' => 190000]);
    $record = resolve(RecordBudgetMovementAction::class);
    $record->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData(['amount' => '2000.00', 'acknowledge' => true]));
    $source = $f['line']->movements()->firstOrFail();
    // Act
    $record->handle($f['event'], 1, budgetKey(), $f['line']->id, budgetMovementData(['kind' => 'refund', 'amount' => '100.00', 'source_id' => $source->id]));
    $data = resolve(EventBudgetViewModel::class)->data($f['event']);
    // Assert
    expect($data['totals']['income']['cash'])->toBe(190000)->and($data['totals']['confirmed_net'])->toBe(190000)->and($data['totals']['balanced'])->toBeTrue()->and($data['rows'][0]['variance'])->toBe(-10000);
});

test('missing amounts and zero lines never pretend to be a complete settlement', function (): void {
    // Arrange
    $f = budgetFixture(['planned_cents' => null, 'confirmed_cents' => null]);
    // Act
    $data = resolve(EventBudgetViewModel::class)->data($f['event']);
    // Assert
    expect($data['totals']['planned_net'])->toBeNull()->and($data['totals']['confirmed_net'])->toBeNull()->and($data['totals']['missing'])->toBe(1)->and($data['totals']['balanced'])->toBeFalse();
    // Act
    resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetLineData(['planned' => '0', 'confirmed' => '0', 'reason' => 'No fee owed']));
    // Assert
    expect(resolve(EventBudgetViewModel::class)->data($f['event'])['totals']['balanced'])->toBeTrue();
});

test('each balance direction is shown independently', function (string $direction, int $confirmed, int $paid, string $attention): void {
    // Arrange
    $f = budgetFixture(['direction' => $direction, 'category' => 'other', 'confirmed_cents' => $confirmed]);
    EventBudgetMovement::factory()->for($f['line'], 'line')->create(['amount_cents' => $paid]);
    // Act
    $data = resolve(EventBudgetViewModel::class)->data($f['event']);
    // Assert
    expect($data['rows'][0]['attention'])->toBe($attention)->and($data['totals'][$attention])->toBe(abs($confirmed - $paid));
})->with([['expense', 50000, 20000, 'to_pay'], ['income', 50000, 20000, 'to_collect'], ['expense', 10000, 20000, 'refund_to_collect'], ['income', 10000, 20000, 'refund_to_send']]);

test('financial history outlives generic activity cleanup and an actor soft delete', function (): void {
    // Arrange
    $f = budgetFixture();
    $actor = User::query()->findOrFail(auth()->id());
    resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetLineData(['notes' => 'Private history note']));
    $revision = $f['budget']->revisions()->firstOrFail();
    $revision->created_at = now()->subYears(2);
    $revision->save();
    // Act
    expect(Artisan::call('activitylog:clean', ['--force' => true, '--days' => 1]))->toBe(0);
    $actor->delete();
    // Assert
    expect(EventBudgetRevision::query()->whereKey($revision->id)->firstOrFail()->actor_label)->not->toBeEmpty()
        ->and(Activity::query()->where('properties', 'like', '%Private history note%')->count())->toBe(0);
});

test('a retained revision keeps its owning budget relationship and readable changed fields', function (): void {
    // Arrange
    $f = budgetFixture();
    resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetLineData(['planned' => '600.00', 'due_date' => today()->toDateString(), 'notes' => 'Private change']));
    $revision = $f['budget']->revisions()->firstOrFail();
    // Act
    $changes = resolve(EventBudgetViewModel::class)->historyChanges([$revision])[$revision->id];
    // Assert
    expect($revision->budget()->firstOrFail()->id)->toBe($f['budget']->id)
        ->and(collect($changes)->pluck('after')->all())->toContain('600.00 USD', today()->toDateString(), 'Private change');
});

test('the budget data is built for whoever the caller lets through', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();
    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $data = resolve(EventBudgetViewModel::class)->data($event);

    // Assert
    expect($data['event']->is($event))->toBeTrue()
        ->and($data['rows'])->toBeEmpty();
});
