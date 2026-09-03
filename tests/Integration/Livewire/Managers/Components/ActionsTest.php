<?php

declare(strict_types=1);

use App\Actions\Managers\ClearFromInjuryAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Livewire\Managers\Components\Actions;
use App\Models\Roster\Managers\Manager;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it renders with the manager mounted', function (): void {
    // Arrange
    $manager = Manager::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Manager',
    ]);

    actingAs(administrator());

    // Act
    $component = livewire(Actions::class, ['manager' => $manager]);

    // Assert
    $component->assertOk();
    expect($component->get('manager'))->toEqual($manager);
});

test('it delegates lifecycle actions and dispatches manager feedback', function (
    string $method,
    string $actionClass,
    DoubleInterface $action,
    string $message,
): void {
    // Arrange
    $manager = Manager::factory()->create();
    $action->expects('handle');
    app()->instance($actionClass, $action);

    actingAs(administrator());
    $component = livewire(Actions::class, ['manager' => $manager]);

    // Act
    $component->call($method);

    // Assert
    $component
        ->assertDispatched('manager-updated')
        ->assertDispatched('flash-message', type: 'status', message: $message);
    $action->verify();
})->with([
    'employ' => ['employ', EmployAction::class, Double::for(EmployAction::class), 'Manager has been hired.'],
    'release' => ['release', ReleaseAction::class, Double::for(ReleaseAction::class), 'Manager contract has been terminated.'],
    'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Manager has been retired.'],
    'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Manager has been brought out of retirement.'],
    'suspend' => ['suspend', SuspendAction::class, Double::for(SuspendAction::class), 'Manager has been suspended.'],
    'injure' => ['injure', InjureAction::class, Double::for(InjureAction::class), 'Manager injury has been recorded.'],
    'clear from injury' => ['clearFromInjury', ClearFromInjuryAction::class, Double::for(ClearFromInjuryAction::class), 'Manager has been cleared from injury.'],
    'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Manager has been restored.'],
]);

test('it reinstates a suspended manager and dispatches feedback', function (): void {
    // Arrange
    $manager = Manager::factory()->suspended()->create();

    actingAs(administrator());
    $component = livewire(Actions::class, ['manager' => $manager]);

    // Act
    $component->call('reinstate');

    // Assert
    $component
        ->assertDispatched('manager-updated')
        ->assertDispatched('flash-message', type: 'status', message: 'Manager has been reinstated.');
    expect($manager->currentSuspension()->exists())->toBeFalse();
});

test('it forbids lifecycle actions for unauthorized users', function (): void {
    // Arrange
    $manager = Manager::factory()->unemployed()->create();

    actingAs(basicUser());
    $component = livewire(Actions::class, ['manager' => $manager]);

    // Act
    $component->call('employ');

    // Assert
    $component->assertForbidden();
    expect($manager->currentEmployment()->exists())->toBeFalse();
});
