<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main;
use App\Models\Events\Event;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Events table authorization', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->basicUser()->create();
    });

    test('an administrator can access the events table', function () {
        // Arrange
        $administrator = $this->administrator;

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);

        // Assert
        $component->assertOk();
    });

    test('a basic user cannot access the events table', function () {
        // Arrange
        $basicUser = $this->basicUser;

        // Act
        actingAs($basicUser);
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('a guest cannot access the events table', function () {
        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('an administrator can manage event records from the table', function () {
        // Arrange
        $administrator = $this->administrator;
        $event = Event::factory()->unscheduled()->create();
        $deletedEvent = Event::factory()->trashed()->create();

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);
        $component->call('delete', $event);
        $component->call('restore', $deletedEvent->id);

        // Assert
        $component->assertHasNoErrors();
    });
});
