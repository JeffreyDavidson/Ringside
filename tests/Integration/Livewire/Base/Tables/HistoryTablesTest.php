<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('history tables', function (): void {
    it('requires the record it lists history for', function (string $table, string $parameter, Closure $parent): void {
        // Arrange
        $subject = Str::of($parameter)->beforeLast('Id')->headline()->lower();

        // Act & Assert
        expect(fn () => app($table)->builder())
            ->toThrow(LogicException::class, "A {$subject} was not provided.");
    })->with('livewire history tables');

    it('is scoped to the record it lists history for', function (string $table, string $parameter, Closure $parent): void {
        // Arrange
        $record = $parent();

        // Act
        $component = livewire($table, [$parameter => $record->getKey()]);

        // Assert
        $component->assertSet($parameter, $record->getKey());
        $component->assertSuccessful();
    })->with('livewire history tables');

    it('renders an empty state when the record has no history', function (
        string $table,
        string $parameter,
        Closure $parent,
        int $basicUserStatus,
        string $heading,
        string $emptyMessage,
        string $placeholder,
    ): void {
        // Arrange
        $record = $parent();

        // Act
        $component = livewire($table, [$parameter => $record->getKey()]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee($heading)
            ->assertSee($emptyMessage)
            ->assertDontSeeHtml("placeholder=\"{$placeholder}\"");
    })->with('livewire history tables');

    it('forbids users without access to the record', function (
        string $table,
        string $parameter,
        Closure $parent,
        int $basicUserStatus,
        string $actor,
    ): void {
        // Arrange
        $record = $parent();

        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire($table, [$parameter => $record->getKey()]);

        // Assert
        $component->assertStatus($actor === 'guest' ? 403 : $basicUserStatus);
    })->with('livewire history tables')->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
