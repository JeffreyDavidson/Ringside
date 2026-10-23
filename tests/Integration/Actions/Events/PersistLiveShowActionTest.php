<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\LockLiveShowAction;
use App\Actions\Events\PersistLiveShowAction;
use App\Data\Events\LiveShowChangeData;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

test('live persistence records a compact event audit and rolls back with its caller', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $count = Activity::query()->where('log_name', 'live-show')->count();

    // Act / Assert
    expect(fn () => DB::transaction(function () use ($fixture): void {
        $run = resolve(LockLiveShowAction::class)->handle($fixture['event'], 1);
        resolve(PersistLiveShowAction::class)->handle($run, new LiveShowChangeData('attendance', $fixture['person'], ['arrived' => null], ['arrived' => now()->toIso8601String()]), signedInUser());

        throw new RuntimeException('Roll back the enclosing operation');
    }))->toThrow(RuntimeException::class);
    expect($fixture['run']->refresh()->version)->toBe(1)->and(Activity::query()->where('log_name', 'live-show')->count())->toBe($count);
});

test('live persistence names the actor passed in as the audit causer', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $actor = administrator();
    $run = resolve(LockLiveShowAction::class)->handle($fixture['event'], 1);

    // Act
    resolve(PersistLiveShowAction::class)->handle($run, new LiveShowChangeData('attendance', $fixture['person'], ['arrived' => null], ['arrived' => now()->toIso8601String()]), $actor);

    // Assert
    $audit = Activity::query()->where('log_name', 'live-show')->latest('id')->firstOrFail();
    expect($audit->causer_id)->toBe($actor->id)
        ->and($audit->causer_type)->toBe($actor->getMorphClass());
});
