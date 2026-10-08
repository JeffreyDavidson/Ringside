<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousManagers;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('wrestler previous managers table', function (): void {
    it('defines the manager history table configuration', function (): void {
        // Arrange
        $table = new PreviousManagers;

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
});
