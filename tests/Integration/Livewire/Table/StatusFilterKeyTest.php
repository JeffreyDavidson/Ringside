<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Referees\Tables\Main as RefereesTable;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Livewire\Users\Tables\Main as UsersTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('table filter keys', function (): void {
    it('keeps the status filter key when the label is translated', function (string $component): void {
        // Arrange
        app('translator')->addLines(['core.status' => 'Estado'], 'en');

        // Act
        $table = livewire($component);

        // Assert
        $table
            ->assertSet('filterValues.status', '')
            ->assertSet('filterValues', fn (array $values): bool => ! array_key_exists('estado', $values));
    })->with([
        'wrestlers' => WrestlersTable::class,
        'managers' => ManagersTable::class,
        'referees' => RefereesTable::class,
        'tag teams' => TagTeamsTable::class,
        'events' => EventsTable::class,
        'users' => UsersTable::class,
    ]);

    it('keeps the date and venue filter keys when their labels are translated', function (string $component, array $keys): void {
        // Arrange
        app('translator')->addLines([
            'core.employment_date' => 'Fecha de empleo',
            'core.activation_date' => 'Fecha de activacion',
            'core.event_dates' => 'Fechas del evento',
            'core.venue' => 'Sede',
        ], 'en');

        // Act
        $table = livewire($component);

        // Assert
        $table->assertSet('filterValues', fn (array $values): bool => array_diff($keys, array_keys($values)) === []);
    })->with([
        'wrestlers' => [WrestlersTable::class, ['employment_date']],
        'managers' => [ManagersTable::class, ['employment_date']],
        'referees' => [RefereesTable::class, ['employment_date']],
        'tag teams' => [TagTeamsTable::class, ['employment_date']],
        'events' => [EventsTable::class, ['event_dates', 'venue']],
    ]);
});
