<?php

declare(strict_types=1);

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Livewire\Titles\Components\Actions;
use App\Models\Titles\Title;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it renders with the title mounted', function (): void {
    // Arrange
    $title = Title::factory()->create(['name' => 'Test Championship Title']);

    actingAs(administrator());

    // Act
    $component = livewire(Actions::class, ['title' => $title]);

    // Assert
    $component
        ->assertOk()
        ->assertViewIs('livewire.titles.components.actions');
    expect($component->get('title'))->toEqual($title);
});

test('it delegates lifecycle actions and dispatches title feedback', function (
    string $method,
    string $actionClass,
    DoubleInterface $action,
    string $message,
): void {
    // Arrange
    $title = Title::factory()->create();
    $action->expects('handle');
    app()->instance($actionClass, $action);

    actingAs(administrator());
    $component = livewire(Actions::class, ['title' => $title]);

    // Act
    $component->call($method);

    // Assert
    $component
        ->assertDispatched('title-updated')
        ->assertDispatched('flash-message', type: 'status', message: $message);
    $action->verify();
})->with([
    'debut' => ['debut', DebutAction::class, Double::for(DebutAction::class), 'Title successfully debuted.'],
    'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Title successfully retired.'],
    'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Title successfully unretired.'],
    'deactivate' => ['deactivate', PullAction::class, Double::for(PullAction::class), 'Title successfully pulled.'],
    'reinstate' => ['reinstate', ReinstateAction::class, Double::for(ReinstateAction::class), 'Title successfully reinstated.'],
    'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Title successfully restored.'],
]);

test('it forbids lifecycle actions for unauthorized users', function (): void {
    // Arrange
    $title = Title::factory()->undebuted()->create();

    actingAs(basicUser());
    $component = livewire(Actions::class, ['title' => $title]);

    // Act
    $component->call('debut');

    // Assert
    $component->assertForbidden();
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
});
