<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\Main;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Tag teams table authorization', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->basicUser()->create();
    });

    test('an administrator can access the tag teams table', function () {
        // Arrange
        $administrator = $this->administrator;

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);

        // Assert
        $component->assertOk();
    });

    test('a basic user cannot access the tag teams table', function () {
        // Arrange
        $basicUser = $this->basicUser;

        // Act
        actingAs($basicUser);
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('a guest cannot access the tag teams table', function () {
        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    });

    test('an administrator can manage tag team records from the table', function () {
        // Arrange
        $administrator = $this->administrator;
        $tagTeam = TagTeam::factory()->create();
        $deletedTagTeam = TagTeam::factory()->trashed()->create();

        // Act
        actingAs($administrator);
        $component = livewire(Main::class);
        $component->call('delete', $tagTeam);
        $component->call('restore', $deletedTagTeam->id);

        // Assert
        $component->assertHasNoErrors();
    });
});
