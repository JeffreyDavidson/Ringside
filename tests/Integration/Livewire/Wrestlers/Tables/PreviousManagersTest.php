<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('requires a wrestler', function (): void {
    expect(fn () => (new PreviousManagers())->builder())
        ->toThrow(LogicException::class, 'A wrestler was not provided.');
});

it('returns only ended manager assignments for the requested wrestler in newest-first order', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $recentManager = Manager::factory()->create();
    $olderManager = Manager::factory()->create();
    $currentManager = Manager::factory()->create();
    $otherManager = Manager::factory()->create();

    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $olderManager->id,
        'hired_at' => Date::now()->subMonths(3),
        'fired_at' => Date::now()->subMonths(2),
    ]);
    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $recentManager->id,
        'hired_at' => Date::now()->subMonth(),
        'fired_at' => Date::now()->subWeek(),
    ]);
    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $currentManager->id,
        'hired_at' => Date::now()->subDays(3),
        'fired_at' => null,
    ]);
    WrestlerManager::query()->create([
        'wrestler_id' => $otherWrestler->id,
        'manager_id' => $otherManager->id,
        'hired_at' => Date::now()->subDays(2),
        'fired_at' => Date::now()->subDay(),
    ]);

    $table = new PreviousManagers();
    $table->wrestlerId = $wrestler->id;

    // Act
    $assignments = $table->builder()->get();

    // Assert
    expect($assignments->pluck('manager_id')->all())->toBe([
        $recentManager->id,
        $olderManager->id,
    ])->and($assignments->every->relationLoaded('manager'))->toBeTrue();
});

it('renders previous manager names and assignment dates', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $previousManager = Manager::factory()->create([
        'first_name' => 'Previous',
        'last_name' => 'Manager',
    ]);
    $currentManager = Manager::factory()->create([
        'first_name' => 'Current',
        'last_name' => 'Manager',
    ]);
    $hiredAt = Date::now()->subMonth();
    $firedAt = Date::now()->subWeek();

    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $previousManager->id,
        'hired_at' => $hiredAt,
        'fired_at' => $firedAt,
    ]);
    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $currentManager->id,
        'hired_at' => Date::now()->subDay(),
        'fired_at' => null,
    ]);

    // Act
    $component = livewire(PreviousManagers::class, ['wrestlerId' => $wrestler->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Previous Manager')
        ->assertSee($hiredAt->format('Y-m-d'))
        ->assertSee($firedAt->format('Y-m-d'))
        ->assertDontSee('Current Manager');
});

it('keeps separate historical assignments for a returning manager', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();

    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'hired_at' => Date::now()->subMonths(4),
        'fired_at' => Date::now()->subMonths(3),
    ]);
    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'hired_at' => Date::now()->subMonths(2),
        'fired_at' => Date::now()->subMonth(),
    ]);

    $table = new PreviousManagers();
    $table->wrestlerId = $wrestler->id;

    // Act
    $assignments = $table->builder()->get();

    // Assert
    expect($assignments)->toHaveCount(2)
        ->and($assignments->pluck('manager_id')->all())->toBe([
            $manager->id,
            $manager->id,
        ]);
});

it('omits deleted managers', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'hired_at' => Date::now()->subMonth(),
        'fired_at' => Date::now()->subWeek(),
    ]);
    $manager->delete();

    $table = new PreviousManagers();
    $table->wrestlerId = $wrestler->id;

    // Act
    $assignments = $table->builder()->get();

    // Assert
    expect($assignments)->toBeEmpty();
});

it('defines the manager history table configuration', function (): void {
    // Arrange
    $table = new PreviousManagers();

    // Act
    $fields = collect($table->columns())
        ->map->getField()
        ->all();

    // Assert
    expect($table->databaseTableName)->toBe('wrestlers_managers')
        ->and($fields)->toBe([
            'manager.full_name',
            'hired_at',
            'fired_at',
        ]);
});

it('forbids users without access to the wrestler', function (string $actor): void {
    $wrestler = Wrestler::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(PreviousManagers::class, ['wrestlerId' => $wrestler->id])
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
