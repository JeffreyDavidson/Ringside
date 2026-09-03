<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;
use App\Models\Events\Venue;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Venues table authorization', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
    });

    test('an administrator can access the venues table', function () {
        // Arrange
        $administrator = $this->administrator;

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);

        // Assert
        $component->assertOk();
    });

    test('a basic user cannot access the venues table', function () {
        // Arrange
        $basicUser = $this->basicUser;

        // Act
        actingAs($basicUser);
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('a guest cannot access the venues table', function () {
        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('an administrator can manage venue records from the table', function () {
        // Arrange
        $administrator = $this->administrator;
        $venue = Venue::factory()->create();
        $deletedVenue = Venue::factory()->trashed()->create();

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);
        $component->call('delete', $venue);
        $component->call('restore', $deletedVenue->id);

        // Assert
        $component->assertHasNoErrors();
    });
});
