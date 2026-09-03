<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ClearFromInjuryAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Livewire\Wrestlers\Components\Actions;
use App\Models\Roster\Wrestlers\Wrestler;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it renders with the wrestler mounted', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);

    actingAs(administrator());

    // Act
    $component = livewire(Actions::class, ['wrestler' => $wrestler]);

    // Assert
    $component->assertOk();
    expect($component->get('wrestler'))->toEqual($wrestler);
});

test('it delegates lifecycle actions and dispatches wrestler feedback', function (
    string $method,
    string $actionClass,
    DoubleInterface $action,
    string $message,
): void {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $action->expects('handle');
    app()->instance($actionClass, $action);

    actingAs(administrator());
    $component = livewire(Actions::class, ['wrestler' => $wrestler]);

    // Act
    $component->call($method);

    // Assert
    $component
        ->assertDispatched('wrestler-updated')
        ->assertDispatched('flash-message', type: 'status', message: $message);
    $action->verify();
})->with([
    'employ' => ['employ', EmployAction::class, Double::for(EmployAction::class), 'Wrestler has been hired.'],
    'release' => ['release', ReleaseAction::class, Double::for(ReleaseAction::class), 'Contract has been terminated.'],
    'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Wrestler has been retired.'],
    'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Wrestler has been brought out of retirement.'],
    'suspend' => ['suspend', SuspendAction::class, Double::for(SuspendAction::class), 'Wrestler has been suspended.'],
    'reinstate' => ['reinstate', ReinstateAction::class, Double::for(ReinstateAction::class), 'Wrestler has been reinstated.'],
    'injure' => ['injure', InjureAction::class, Double::for(InjureAction::class), 'Injury has been recorded.'],
    'clear from injury' => ['clearFromInjury', ClearFromInjuryAction::class, Double::for(ClearFromInjuryAction::class), 'Wrestler has been cleared from injury.'],
    'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Wrestler has been restored.'],
]);

test('it forbids lifecycle actions for unauthorized users', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();

    actingAs(basicUser());
    $component = livewire(Actions::class, ['wrestler' => $wrestler]);

    // Act
    $component->call('employ');

    // Assert
    $component->assertForbidden();
    expect($wrestler->currentEmployment()->exists())->toBeFalse();
});
