<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RetireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Managers\Components\Actions;
use App\Models\Roster\Managers\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('manager actions component', function (): void {
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

    test('it brings a manager who was employed when retired back as employed', function (): void {
        // Arrange
        $manager = Manager::factory()->employed()->create();
        resolve(RetireAction::class)->handle($manager, now()->subHours(2));

        actingAs(administrator());
        $component = livewire(Actions::class, ['manager' => $manager->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched('manager-updated');
        expect($manager->refresh()->status)->toBe(EmploymentStatus::Employed);
    });

    test('it brings a manager who was released before retiring back as released', function (): void {
        // Arrange
        $manager = Manager::factory()->employed()->create();
        resolve(ReleaseAction::class)->handle($manager, now()->subHours(3));
        resolve(RetireAction::class)->handle($manager->refresh(), now()->subHours(2));

        actingAs(administrator());
        $component = livewire(Actions::class, ['manager' => $manager->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched('manager-updated');
        expect($manager->refresh()->status)->toBe(EmploymentStatus::Released);
    });
});
