<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\Main;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Wrestlers table authorization', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->basicUser()->create();
    });

    test('an administrator can access the wrestlers table', function () {
        // Arrange
        $administrator = $this->administrator;

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);

        // Assert
        $component->assertOk();
    });

    test('a basic user cannot access the wrestlers table', function () {
        // Arrange
        $basicUser = $this->basicUser;

        // Act
        actingAs($basicUser);
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('a guest cannot access the wrestlers table', function () {
        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('an administrator can manage wrestler records from the table', function () {
        // Arrange
        $administrator = $this->administrator;
        $wrestler = Wrestler::factory()->create();
        $deletedWrestler = Wrestler::factory()->trashed()->create();

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);
        $component->call('delete', $wrestler);
        $component->call('restore', $deletedWrestler->id);

        // Assert
        $component->assertHasNoErrors();
    });
});
