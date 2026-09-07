<?php

declare(strict_types=1);

use App\Livewire\Table\Columns\DateColumn;
use Illuminate\Support\Carbon;

describe('date column values', function (): void {
    test('formats supported date values with the default output format', function (string|DateTimeInterface $input): void {
        // Arrange
        $column = DateColumn::make('Started At');

        // Act
        $value = $column->resolveValue(['started_at' => $input]);

        // Assert
        expect($value)->toBe('2026-08-15');
    })->with([
        'database timestamp' => ['2026-08-15 19:30:00'],
        'mutable date' => [Carbon::parse('2026-08-15 19:30:00')],
        'immutable date' => [new DateTimeImmutable('2026-08-15 19:30:00')],
    ]);

    test('honors custom input and output formats', function (): void {
        // Arrange
        $column = DateColumn::make('Event Date', 'scheduled_at')
            ->inputFormat('d/m/Y H:i')
            ->outputFormat('M j, Y g:i A');

        // Act
        $value = $column->resolveValue(['scheduled_at' => '15/08/2026 19:30']);

        // Assert
        expect($value)->toBe('Aug 15, 2026 7:30 PM');
    });

    test('uses the configured placeholder for absent dates', /** @param array{started_at?: string|null} $row */ function (array $row): void {
        // Arrange
        $column = DateColumn::make('Started At')->emptyValue('Not scheduled');

        // Act
        $value = $column->resolveValue($row);

        // Assert
        expect($value)->toBe('Not scheduled');
    })->with([
        'missing field' => [[]],
        'null date' => [['started_at' => null]],
        'empty string' => [['started_at' => '']],
    ]);

    test('rejects unsupported date value types', function (mixed $input): void {
        // Arrange
        $column = DateColumn::make('Started At');

        // Act / Assert
        expect(fn () => $column->resolveValue(['started_at' => $input]))
            ->toThrow(LogicException::class, 'Date column values must be date objects or formatted strings.');
    })->with([
        'integer' => [42],
        'boolean' => [false],
        'array' => [[]],
        'non-date object' => [new stdClass()],
    ]);
});
