<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RestoreAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Livewire\TagTeams\Components\Actions;
use App\Models\Roster\TagTeams\TagTeam;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it renders with the tag team mounted', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create(['name' => 'Test Tag Team']);

    actingAs(administrator());

    // Act
    $component = livewire(Actions::class, ['tagTeam' => $tagTeam]);

    // Assert
    $component->assertOk();
    expect($component->get('tagTeam'))->toEqual($tagTeam);
});

test('it delegates lifecycle actions and dispatches tag team feedback', function (
    string $method,
    string $actionClass,
    DoubleInterface $action,
    string $message,
): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $action->expects('handle');
    app()->instance($actionClass, $action);

    actingAs(administrator());
    $component = livewire(Actions::class, ['tagTeam' => $tagTeam]);

    // Act
    $component->call($method);

    // Assert
    $component
        ->assertDispatched('tag-team-updated')
        ->assertDispatched('flash-message', type: 'status', message: $message);
    $action->verify();
})->with([
    'employ' => ['employ', EmployAction::class, Double::for(EmployAction::class), 'Tag team has been hired.'],
    'release' => ['release', ReleaseAction::class, Double::for(ReleaseAction::class), 'Tag team contract has been terminated.'],
    'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Tag team has been retired.'],
    'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Tag team has been brought out of retirement.'],
    'suspend' => ['suspend', SuspendAction::class, Double::for(SuspendAction::class), 'Tag team has been suspended.'],
    'reinstate' => ['reinstate', ReinstateAction::class, Double::for(ReinstateAction::class), 'Tag team has been reinstated.'],
    'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Tag team has been restored.'],
]);

test('it forbids lifecycle actions for unauthorized users', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->unemployed()->create();

    actingAs(basicUser());
    $component = livewire(Actions::class, ['tagTeam' => $tagTeam]);

    // Act
    $component->call('employ');

    // Assert
    $component->assertForbidden();
    expect($tagTeam->currentEmployment()->exists())->toBeFalse();
});
