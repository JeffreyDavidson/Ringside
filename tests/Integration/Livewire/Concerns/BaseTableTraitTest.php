<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('table configuration', function (): void {
    it('configures an index table for its resource', function (): void {
        // Act
        $table = livewire(Main::class);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search venues"')
            ->assertSee('Venues')
            ->assertSee('Add Venue')
            ->assertSee(__('core.actions'));
    });
});

describe('pagination', function (): void {
    it('accepts a shared pagination option', function (int $perPage): void {
        // Arrange
        $table = livewire(Main::class);

        // Act
        $table->set('perPage', $perPage);

        // Assert
        $table->assertSet('perPage', $perPage);
    })->with([5, 10, 25, 50, 100]);

    it('rejects unsupported pagination options', function (int $perPage): void {
        // Arrange
        $table = livewire(Main::class);

        // Act
        $table->set('perPage', $perPage);

        // Assert
        $table->assertSet('perPage', 5);
    })->with([0, 15, 999]);
});
