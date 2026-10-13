<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\LockLiveShowAction;
use Illuminate\Support\Facades\DB;

test('a live mutation locks its owning event before the live row and checks the fresh version', function (): void {
    // Arrange
    $fixture = liveShowFixture();

    // Act
    $statements = recordStatements(fn () => DB::transaction(fn () => resolve(LockLiveShowAction::class)->handle($fixture['event'], 1)));

    // Assert
    $eventLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));
    $runLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_live_runs"'));
    expect($eventLock)->toBeLessThan($runLock)->and($statements[$runLock]['bindings'])->toBe([$fixture['event']->id]);
});
