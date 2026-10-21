<?php

declare(strict_types=1);

use App\Enums\Events\BudgetOperation;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetRevision;
use App\Queries\Events\BudgetRevisionHistoryQuery;
use Illuminate\Support\Str;

require_once __DIR__.'/../../../Fixtures/budget.php';

/**
 * @param  array<string, mixed>  $before
 * @param  array<string, mixed>  $after
 */
function budgetRevision(EventBudget $budget, int $version, BudgetOperation $operation, int $targetId, array $before = [], array $after = []): EventBudgetRevision
{
    $revision = new EventBudgetRevision;
    $revision->event_budget_id = $budget->id;
    $revision->version = $version;
    $revision->request_key = (string) Str::uuid();
    $revision->request_hash = hash('sha256', (string) $version);
    $revision->operation = $operation;
    $revision->target_id = $targetId;
    $revision->before = $before;
    $revision->after = $after;
    $revision->actor_id = (int) auth()->id();
    $revision->actor_label = 'Test Admin';
    $revision->save();

    return $revision;
}

test('lists every revision of the budget newest first when no line is chosen', function (): void {
    // Arrange
    $f = budgetFixture();
    $first = budgetRevision($f['budget'], 1, BudgetOperation::SaveLine, $f['line']->id);
    $second = budgetRevision($f['budget'], 2, BudgetOperation::RecordMovement, 99, [], ['event_budget_line_id' => $f['line']->id]);

    // Act
    $ids = resolve(BudgetRevisionHistoryQuery::class)->get($f['budget'], null)->pluck('id')->all();

    // Assert
    expect($ids)->toBe([$second->id, $first->id]);
});

test('narrows the history to one line by line edits and by movements that name the line', function (): void {
    // Arrange
    $f = budgetFixture();
    $other = EventBudgetLine::factory()->for($f['budget'], 'budget')->create();
    $saved = budgetRevision($f['budget'], 1, BudgetOperation::SaveLine, $f['line']->id);
    budgetRevision($f['budget'], 2, BudgetOperation::SaveLine, $other->id);
    $recorded = budgetRevision($f['budget'], 3, BudgetOperation::RecordMovement, 50, [], ['event_budget_line_id' => $f['line']->id]);
    budgetRevision($f['budget'], 4, BudgetOperation::RecordMovement, 51, [], ['event_budget_line_id' => $other->id]);
    $corrected = budgetRevision($f['budget'], 5, BudgetOperation::CorrectMovement, 50, ['event_budget_line_id' => $f['line']->id]);
    $archived = budgetRevision($f['budget'], 6, BudgetOperation::ArchiveLine, $f['line']->id);
    budgetRevision($f['budget'], 7, BudgetOperation::CorrectMovement, $f['line']->id);

    // Act
    $ids = resolve(BudgetRevisionHistoryQuery::class)->get($f['budget'], $f['line']->id)->pluck('id')->all();

    // Assert
    expect($ids)->toBe([$archived->id, $corrected->id, $recorded->id, $saved->id]);
});

test('leaves out revisions that belong to another budget', function (): void {
    // Arrange
    $f = budgetFixture();
    $own = budgetRevision($f['budget'], 1, BudgetOperation::SaveLine, $f['line']->id);
    $foreign = budgetFixture();
    budgetRevision($foreign['budget'], 1, BudgetOperation::SaveLine, $foreign['line']->id);

    // Act
    $ids = resolve(BudgetRevisionHistoryQuery::class)->get($f['budget'], null)->pluck('id')->all();

    // Assert
    expect($ids)->toBe([$own->id]);
});
