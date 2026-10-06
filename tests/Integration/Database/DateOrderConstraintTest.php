<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

dataset('periodTables', fn (): array => periodTableDataset());

test('a period ending before it starts cannot be inserted', function (string $table, string $start, string $end) {
    // Arrange
    $columns = periodRowColumns($table);

    // Act
    $insert = fn () => DB::transaction(fn () => DB::table($table)->insert([
        ...$columns,
        $start => '2026-03-10 12:00:00',
        $end => '2026-03-09 12:00:00',
    ]));

    // Assert
    expect($insert)->toThrow(QueryException::class);
})->with('periodTables');

test('a period cannot be updated to end before it starts', function (string $table, string $start, string $end) {
    // Arrange
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-11 12:00:00']);
    $id = DB::table($table)->value('id');

    // Act
    $update = fn () => DB::transaction(fn () => DB::table($table)->where('id', $id)->update([$end => '2026-03-09 12:00:00']));

    // Assert
    expect($update)->toThrow(QueryException::class);
})->with('periodTables');

test('a period cannot be updated to start after it ends', function (string $table, string $start, string $end) {
    // Arrange
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-11 12:00:00']);
    $id = DB::table($table)->value('id');

    // Act
    $update = fn () => DB::transaction(fn () => DB::table($table)->where('id', $id)->update([$start => '2026-03-12 12:00:00']));

    // Assert
    expect($update)->toThrow(QueryException::class);
})->with('periodTables');

test('a period may end on the same moment it starts', function (string $table, string $start, string $end) {
    // Act
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-10 12:00:00']);

    // Assert
    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('a period may end later than it starts', function (string $table, string $start, string $end) {
    // Act
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-10 12:00:01']);

    // Assert
    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('an open period passes', function (string $table, string $start, string $end) {
    // Act
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => null]);

    // Assert
    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('a membership without a start date may still have an end date', function () {
    // Act
    DB::table('tag_teams_wrestlers')->insert([...periodRowColumns('tag_teams_wrestlers'), 'joined_at' => null, 'left_at' => '2026-03-10 12:00:00']);

    // Assert
    expect(DB::table('tag_teams_wrestlers')->count())->toBe(1);
});

test('a soft-deleted reign ending before it was won is rejected too', function () {
    // Arrange
    $columns = periodRowColumns('titles_championships');

    // Act
    $insert = fn () => DB::transaction(fn () => DB::table('titles_championships')->insert([
        ...$columns,
        'won_at' => '2026-03-10 12:00:00',
        'lost_at' => '2026-03-09 12:00:00',
        'deleted_at' => '2026-03-11 12:00:00',
    ]));

    // Assert
    expect($insert)->toThrow(QueryException::class);
});
