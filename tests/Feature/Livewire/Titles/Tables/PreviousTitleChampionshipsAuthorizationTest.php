<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\PreviousTitleChampionships;
use App\Models\Titles\Title;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Previous title championships authorization', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->basicUser()->create();
        $this->title = Title::factory()->create();
    });

    test('an administrator can access previous title championships', function () {
        // Arrange
        $administrator = $this->administrator;

        // Act
        actingAs($administrator);
        $component = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $component->assertOk();
    });

    test('a basic user cannot access previous title championships', function () {
        // Arrange
        $basicUser = $this->basicUser;

        // Act
        actingAs($basicUser);
        $component = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $component->assertForbidden();
    });

    test('a guest cannot access previous title championships', function () {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $component->assertForbidden();
    });
});
