<?php

declare(strict_types=1);

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
});
