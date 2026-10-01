<?php

declare(strict_types=1);

use App\Enums\Stables\StableLifecycleAction;
use App\Enums\Stables\StableStatus;
use App\Livewire\Stables\Components\Actions;
use App\Models\Roster\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('stable actions component', function (): void {
    test('it renders with the stable mounted', function (): void {
        // Arrange
        $stable = Stable::factory()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        $component
            ->assertOk()
            ->assertViewIs('livewire.stables.components.actions');
        expect($component->get('stable'))->toEqual($stable);
    });

    test('it only shows the lifecycle buttons that fit the stable state', function (
        string $state,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        foreach ($visible as $method) {
            $component->assertSeeHtml("wire:click=\"{$method}\"");
        }

        foreach ($hidden as $method) {
            $component->assertDontSeeHtml("wire:click=\"{$method}\"");
        }
    })->with([
        'unformed' => ['withNoMembers', [], ['establish', 'disband', 'retire', 'unretire']],
        'ready to establish' => ['withEmployedDefaultMembers', ['establish'], ['disband', 'retire', 'unretire']],
        'active' => ['active', ['disband', 'retire'], ['establish', 'unretire']],
        'disbanded' => ['inactive', ['retire'], ['establish', 'disband', 'unretire']],
        'retired' => ['retired', ['unretire'], ['establish', 'disband', 'retire']],
    ]);

    test('it runs the real action, refreshes the card and reports success', function (
        string $state,
        string $method,
        StableStatus $status,
        string $message,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertDispatched('stable-updated')
            ->assertDispatched('flash-message', type: 'status', message: $message);
        expect($stable->refresh()->status)->toBe($status);
    })->with([
        'establish' => ['withEmployedDefaultMembers', 'establish', StableStatus::Active, 'Stable successfully established.'],
        'disband' => ['active', 'disband', StableStatus::Inactive, 'Stable successfully disbanded.'],
        'retire' => ['active', 'retire', StableStatus::Retired, 'Stable successfully retired.'],
        'unretire' => ['retired', 'unretire', StableStatus::Active, 'Stable successfully unretired.'],
    ]);

    test('it shows the buttons for the new state after a lifecycle action succeeds', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call('disband');

        // Assert
        $component
            ->assertSeeHtml('wire:click="retire"')
            ->assertDontSeeHtml('wire:click="disband"');
    });

    test('it rejects an ineligible action with error feedback and no change', function (
        string $state,
        string $method,
        string $message,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();
        $statusBefore = $stable->status;

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertNotDispatched('stable-updated')
            ->assertDispatched(
                'flash-message',
                fn (string $event, array $params): bool => $params['type'] === 'error'
                    && str_contains($params['message'], $message),
            );
        expect($stable->refresh()->status)->toBe($statusBefore);
    })->with([
        'disband an unformed stable' => ['withNoMembers', 'disband', 'is not active and cannot be disbanded.'],
        'establish without enough members' => ['withNoMembers', 'establish', 'requires at least'],
        'retire an unformed stable' => ['withNoMembers', 'retire', 'is not currently active and cannot be retired.'],
        'unretire an active stable' => ['active', 'unretire', 'is not retired and cannot be unretired.'],
    ]);

    test('it forbids lifecycle actions for unauthorized users without feedback', function (string $method): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(basicUser());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call($method);

        // Assert
        $component->assertForbidden();
        $component
            ->assertNotDispatched('stable-updated')
            ->assertNotDispatched('flash-message');
        expect($stable->refresh()->status)->toBe(StableStatus::Active);
    })->with([
        'establish',
        'disband',
        'retire',
        'unretire',
    ]);

    test('it hides eligible lifecycle actions from users who are not authorized', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(basicUser());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        expect($component->instance()->canPerform(StableLifecycleAction::Disband))->toBeFalse();
        $component->assertDontSeeHtml('wire:click="disband"');
    });
});
