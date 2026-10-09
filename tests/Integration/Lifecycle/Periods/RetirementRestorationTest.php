<?php

declare(strict_types=1);

use App\Actions\Managers\DeleteAction as ManagerDeleteAction;
use App\Actions\Managers\RestoreAction as ManagerRestoreAction;
use App\Actions\Referees\DeleteAction as RefereeDeleteAction;
use App\Actions\Referees\RestoreAction as RefereeRestoreAction;
use App\Actions\Titles\DeleteAction as TitleDeleteAction;
use App\Actions\Titles\RestoreAction as TitleRestoreAction;
use App\Actions\Wrestlers\DeleteAction as WrestlerDeleteAction;
use App\Actions\Wrestlers\RestoreAction as WrestlerRestoreAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Enums\Titles\TitleStatus;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

use function Pest\Laravel\travelTo;

dataset('retirable records', [
    'wrestler' => [
        fn (): Wrestler => Wrestler::factory()->retired()->create(),
        WrestlerDeleteAction::class,
        WrestlerRestoreAction::class,
        EmploymentStatus::Retired,
    ],
    'manager' => [
        fn (): Manager => Manager::factory()->retired()->create(),
        ManagerDeleteAction::class,
        ManagerRestoreAction::class,
        EmploymentStatus::Retired,
    ],
    'referee' => [
        fn (): Referee => Referee::factory()->retired()->create(),
        RefereeDeleteAction::class,
        RefereeRestoreAction::class,
        EmploymentStatus::Retired,
    ],
    'title' => [
        fn (): Title => Title::factory()->retired()->create(),
        TitleDeleteAction::class,
        TitleRestoreAction::class,
        TitleStatus::Retired,
    ],
]);

dataset('unretired records', [
    'wrestler' => [
        fn (): Wrestler => Wrestler::factory()->create(),
        WrestlerDeleteAction::class,
        WrestlerRestoreAction::class,
    ],
    'manager' => [
        fn (): Manager => Manager::factory()->create(),
        ManagerDeleteAction::class,
        ManagerRestoreAction::class,
    ],
    'referee' => [
        fn (): Referee => Referee::factory()->create(),
        RefereeDeleteAction::class,
        RefereeRestoreAction::class,
    ],
    'title' => [
        fn (): Title => Title::factory()->create(),
        TitleDeleteAction::class,
        TitleRestoreAction::class,
    ],
]);

test('restoring a deleted retired record brings it back as retired', function (
    Closure $make,
    string $delete,
    string $restore,
    EmploymentStatus|TitleStatus $status,
): void {
    // Arrange
    $record = $make();
    $retirement = $record->currentRetirement()->firstOrFail();
    resolve($delete)->handle($record);
    $record = $record::withTrashed()->findOrFail($record->getKey());
    expect($retirement->refresh()->ended_at)->not->toBeNull();

    // Act
    resolve($restore)->handle($record);

    // Assert
    $record = $record::query()->findOrFail($record->getKey());
    expect($record->status)->toBe($status)
        ->and($record->retirements()->count())->toBe(1)
        ->and($record->currentRetirement()->firstOrFail()->is($retirement))->toBeTrue()
        ->and($record->lifecycleTransitions()->where('transition', LifecycleTransitionType::Unretired)->exists())->toBeFalse();
})->with('retirable records');

test('restoring a record that was not retired when deleted leaves it without a retirement', function (
    Closure $make,
    string $delete,
    string $restore,
): void {
    // Arrange
    $record = $make();
    resolve($delete)->handle($record);
    $record = $record::withTrashed()->findOrFail($record->getKey());

    // Act
    resolve($restore)->handle($record);

    // Assert
    expect($record->retirements()->count())->toBe(0);
})->with('unretired records');

test('restoring a retired record does not re-employ it', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    resolve(WrestlerDeleteAction::class)->handle($wrestler);
    $wrestler = Wrestler::withTrashed()->findOrFail($wrestler->id);

    // Act
    resolve(WrestlerRestoreAction::class)->handle($wrestler);

    // Assert
    expect($wrestler->currentEmployment()->exists())->toBeFalse()
        ->and($wrestler->employments()->whereNull('ended_at')->exists())->toBeFalse();
});

test('restoring does not reopen suspension or injury closed by the deletion', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    $wrestler->suspensions()->create(['started_at' => now()->subDay()]);
    $wrestler->injuries()->create(['started_at' => now()->subDay()]);
    resolve(WrestlerDeleteAction::class)->handle($wrestler);
    $wrestler = Wrestler::withTrashed()->findOrFail($wrestler->id);

    // Act
    resolve(WrestlerRestoreAction::class)->handle($wrestler);

    // Assert
    expect($wrestler->currentRetirement()->exists())->toBeTrue()
        ->and($wrestler->currentSuspension()->exists())->toBeFalse()
        ->and($wrestler->currentInjury()->exists())->toBeFalse();
});

test('restoring does not reopen a retirement that ended before the deletion', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $retirement = $wrestler->retirements()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(5),
    ]);
    resolve(WrestlerDeleteAction::class)->handle($wrestler, now()->subDay());
    $wrestler = Wrestler::withTrashed()->findOrFail($wrestler->id);

    // Act
    resolve(WrestlerRestoreAction::class)->handle($wrestler);

    // Assert
    expect($retirement->refresh()->ended_at)->not->toBeNull()
        ->and($wrestler->currentRetirement()->exists())->toBeFalse();
});

test('restoring does not reopen a retirement ended by an unretirement on the deletion date', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    $date = now();
    $wrestler->currentRetirement()->firstOrFail()->update(['ended_at' => $date]);
    $wrestler->lifecycleTransitions()->create([
        'dimension' => 'retirement',
        'transition' => LifecycleTransitionType::Unretired,
        'effective_at' => $date,
    ]);
    resolve(WrestlerDeleteAction::class)->handle($wrestler, $date);
    $wrestler = Wrestler::withTrashed()->findOrFail($wrestler->id);

    // Act
    resolve(WrestlerRestoreAction::class)->handle($wrestler);

    // Assert
    expect($wrestler->currentRetirement()->exists())->toBeFalse();
});

test('restoring reopens a future retirement that the deletion closed on its own start date', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $retirement = $wrestler->retirements()->create(['started_at' => now()->addDays(10)->startOfSecond()]);
    resolve(WrestlerDeleteAction::class)->handle($wrestler);
    $wrestler = Wrestler::withTrashed()->findOrFail($wrestler->id);

    // Act
    resolve(WrestlerRestoreAction::class)->handle($wrestler);

    // Assert
    expect($retirement->refresh()->ended_at)->toBeNull();
});

test('a second delete and restore cycle keeps the record retired', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    resolve(WrestlerDeleteAction::class)->handle($wrestler);
    resolve(WrestlerRestoreAction::class)->handle(Wrestler::withTrashed()->findOrFail($wrestler->id));
    travelTo(now()->addDay());

    // Act
    resolve(WrestlerDeleteAction::class)->handle(Wrestler::query()->findOrFail($wrestler->id));
    resolve(WrestlerRestoreAction::class)->handle(Wrestler::withTrashed()->findOrFail($wrestler->id));

    // Assert
    expect($wrestler->refresh()->status)->toBe(EmploymentStatus::Retired)
        ->and($wrestler->retirements()->count())->toBe(1);
});
