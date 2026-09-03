<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\Main;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Stables table authorization', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->basicUser()->create();
    });

    test('an administrator can access the stables table', function () {
        // Arrange
        $administrator = $this->administrator;

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);

        // Assert
        $component->assertOk();
    });

    test('a basic user cannot access the stables table', function () {
        // Arrange
        $basicUser = $this->basicUser;

        // Act
        actingAs($basicUser);
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('a guest cannot access the stables table', function () {
        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });
});
