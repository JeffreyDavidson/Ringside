<?php

declare(strict_types=1);

use App\Livewire\Table\Column;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

describe('table column search', function (): void {
    test('default search matches a substring of the configured field', function (): void {
        // Arrange
        $matching = User::factory()->create(['first_name' => 'Clara', 'email' => 'match@example.com']);
        User::factory()->create(['first_name' => 'Other', 'email' => 'clara@example.com']);
        $query = (new User())->newQuery();
        $column = Column::make('Name', 'first_name')->searchable();

        // Act
        $column->applySearch($query, 'lar');
        $matches = $query->get();

        // Assert
        expect($matches->modelKeys())->toBe([$matching->id]);
    });

    test('custom search replaces the default field constraint', function (): void {
        // Arrange
        $matching = User::factory()->create(['first_name' => 'Chosen', 'email' => 'target@example.com']);
        User::factory()->create(['first_name' => 'Other', 'email' => 'prefix-target@example.com']);
        User::factory()->create(['first_name' => 'target@example.com', 'email' => 'other@example.com']);
        $query = (new User())->newQuery();
        $column = Column::make('Name', 'first_name')->searchable(
            /** @param Builder<User> $query */
            function (Builder $query, string $term): void {
                $query->where('email', $term);
            },
        );

        // Act
        $column->applySearch($query, 'target@example.com');
        $matches = $query->get();

        // Assert
        expect($matches->modelKeys())->toBe([$matching->id]);
    });
});

describe('table column values', function (): void {
    test('view columns render their configured view', function (): void {
        // Arrange
        $column = Column::make('Divider')->view('components.auth.form-divider');

        // Act
        $value = $column->resolveValue(null);

        // Assert
        expect($value)->toContain('items-center')->toContain('Or');
    });

    test('supported field values resolve to display strings', function (mixed $input, string $expected): void {
        // Arrange
        $column = Column::make('Value');

        // Act
        $value = $column->resolveValue(['value' => $input]);

        // Assert
        expect($value)->toBe($expected);
    })->with([
        'text' => ['Championship', 'Championship'],
        'integer' => [42, '42'],
        'zero' => [0, '0'],
        'decimal' => [12.5, '12.5'],
        'true' => [true, '1'],
        'false' => [false, ''],
        'null' => [null, ''],
        'stringable' => [Str::of('Formatted'), 'Formatted'],
    ]);

    test('missing fields render as empty strings', function (): void {
        // Arrange
        $column = Column::make('Missing');

        // Act
        $value = $column->resolveValue([]);

        // Assert
        expect($value)->toBe('');
    });

    test('unsupported field values fail explicitly', function (mixed $input): void {
        // Arrange
        $column = Column::make('Value');

        // Act / Assert
        expect(fn () => $column->resolveValue(['value' => $input]))
            ->toThrow(LogicException::class, 'Table column values must be stringable.');
    })->with([
        'array' => [[]],
        'non-stringable object' => [new stdClass()],
    ]);

    test('label callbacks receive the row and configured column', function (): void {
        // Arrange
        $column = Column::make('Score')
            ->label(
                /** @param array{score: int} $row */
                fn (array $row, Column $column): string => "{$column->getTitle()}: {$row['score']}",
            );

        // Act
        $value = $column->resolveValue(['score' => 42]);

        // Assert
        expect($value)->toBe('Score: 42');
    });
});
