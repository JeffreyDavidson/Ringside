<?php

declare(strict_types=1);

use JMac\Testing\DoubleInterface;
use JMac\Testing\Matching\Argument;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('detail page actions components', function (): void {
    it('renders with the record mounted', function (
        string $component,
        string $property,
        string $model,
        Closure $assertView,
    ): void {
        // Arrange
        $record = $model::factory()->create();

        actingAs(administrator());

        // Act
        $livewire = livewire($component, [$property => $record]);

        // Assert
        $livewire->assertOk();
        $assertView($livewire);
        expect($livewire->get($property))->toEqual($record);
    })->with('lifecycle action components');

    it('delegates lifecycle actions and dispatches feedback', function (
        string $component,
        string $property,
        string $model,
        string $event,
        string $method,
        string $actionClass,
        DoubleInterface $action,
        string $message,
    ): void {
        // Arrange
        $record = $model::factory()->create();
        $action->expects('handle')->with(
            Argument::satisfies(fn (mixed $actual): bool => $actual instanceof $model && $record->is($actual)),
        );
        app()->instance($actionClass, $action);

        actingAs(administrator());
        $livewire = livewire($component, [$property => $record]);

        // Act
        $livewire->call($method);

        // Assert
        $livewire
            ->assertDispatched($event)
            ->assertDispatched('flash-message', type: 'status', message: $message);
        $action->verify();
    })->with('lifecycle action delegations');

    it('forbids lifecycle actions for unauthorized users without success feedback', function (
        string $component,
        string $property,
        string $model,
        string $event,
        string $state,
        string $method,
        string $period,
        bool $periodExists,
    ): void {
        // Arrange
        $record = $model::factory()->{$state}()->create();
        $statusBefore = $record->status;

        actingAs(basicUser());
        $livewire = livewire($component, [$property => $record]);

        // Act
        $livewire->call($method);

        // Assert
        $livewire->assertForbidden();
        $livewire
            ->assertNotDispatched($event)
            ->assertNotDispatched('flash-message');
        expect(session()->has('status'))->toBeFalse()
            ->and($record->{$period}()->exists())->toBe($periodExists)
            ->and($record->refresh()->status)->toBe($statusBefore);
    })->with('lifecycle action refusals');

    it('only shows the lifecycle buttons that fit the record state', function (
        string $component,
        string $property,
        string $model,
        string $state,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $record = $model::factory()->{$state}()->create();

        actingAs(administrator());

        // Act
        $livewire = livewire($component, [$property => $record]);

        // Assert
        foreach ($visible as $method) {
            $livewire->assertSeeHtml("wire:click=\"{$method}\"");
        }

        foreach ($hidden as $method) {
            $livewire->assertDontSeeHtml("wire:click=\"{$method}\"");
        }
    })->with('lifecycle action buttons by state');

    it('shows the buttons for the new state after a lifecycle action succeeds', function (
        string $component,
        string $property,
        string $model,
        string $event,
        string $state,
        array $steps,
    ): void {
        // Arrange
        $record = $model::factory()->{$state}()->create();

        actingAs(administrator());
        $livewire = livewire($component, [$property => $record]);

        foreach ($steps as [$method, $appears, $disappears]) {
            // Act
            $livewire->call($method);

            // Assert
            $livewire
                ->assertDispatched($event)
                ->assertSeeHtml("wire:click=\"{$appears}\"")
                ->assertDontSeeHtml("wire:click=\"{$disappears}\"");
        }
    })->with('lifecycle action button transitions');

    it('hides eligible lifecycle actions from users who are not authorized', function (
        string $component,
        string $property,
        string $model,
        string $state,
        Closure $canPerform,
        string $method,
    ): void {
        // Arrange
        $record = $model::factory()->{$state}()->create();

        actingAs(basicUser());

        // Act
        $livewire = livewire($component, [$property => $record]);

        // Assert
        expect($canPerform($livewire->instance()))->toBeFalse();
        $livewire->assertDontSeeHtml("wire:click=\"{$method}\"");
    })->with('lifecycle action eligibility');
});
