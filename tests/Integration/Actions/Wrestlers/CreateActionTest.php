<?php

declare(strict_types=1);

use App\Actions\Wrestlers\CreateAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\ValueObjects\Height;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it creates a wrestler with basic information', function () {
    $data = new WrestlerData(
        name: 'John Cena',
        height: Height::fromFeetAndInches(6, 1),
        hometown: 'West Newbury, Massachusetts',
        weight: 251,
        signature_move: 'Attitude Adjustment'
    );

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Wrestler::class);
    expect($result->name)->toBe('John Cena');
    expect($result->height->feet)->toBe(6);
    expect($result->height->inches)->toBe(1);
    expect($result->hometown)->toBe('West Newbury, Massachusetts');
    expect($result->weight)->toBe(251);
    expect($result->signature_move)->toBe('Attitude Adjustment');

    $this->assertDatabaseHas('wrestlers', [
        'name' => 'John Cena',
        'hometown' => 'West Newbury, Massachusetts',
        'weight' => 251,
        'signature_move' => 'Attitude Adjustment',
    ]);

    // Should not create employment record when no employment date provided
    $this->assertDatabaseMissing('wrestlers_employments', [
        'wrestler_id' => $result->id,
    ]);
});

test('it creates a wrestler with employment when employment date is provided', function () {
    $employmentDate = now();

    $data = new WrestlerData(
        name: 'The Rock',
        height: Height::fromFeetAndInches(6, 5),
        hometown: 'Miami, Florida',
        weight: 260,
        signature_move: 'Rock Bottom',
        employment_date: $employmentDate
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe('The Rock');
    expect($result->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('wrestlers', [
        'name' => 'The Rock',
        'hometown' => 'Miami, Florida',
        'weight' => 260,
        'signature_move' => 'Rock Bottom',
    ]);

    // Should create employment record
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it creates wrestler with all optional fields', function () {
    $employmentDate = now();

    $data = new WrestlerData(
        name: 'Stone Cold Steve Austin',
        height: Height::fromFeetAndInches(6, 2),
        hometown: 'Austin, Texas',
        weight: 252,
        signature_move: 'Stone Cold Stunner',
        employment_date: $employmentDate
    );

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Wrestler::class);
    expect($result->name)->toBe('Stone Cold Steve Austin');
    expect($result->height->feet)->toBe(6);
    expect($result->height->inches)->toBe(2);
    expect($result->hometown)->toBe('Austin, Texas');
    expect($result->weight)->toBe(252);
    expect($result->signature_move)->toBe('Stone Cold Stunner');

    // Verify database state
    $this->assertDatabaseHas('wrestlers', [
        'id' => $result->id,
        'name' => 'Stone Cold Steve Austin',
        'hometown' => 'Austin, Texas',
        'weight' => 252,
        'signature_move' => 'Stone Cold Stunner',
    ]);

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles height conversion correctly', function () {
    $data = new WrestlerData(
        name: 'Test Wrestler',
        height: Height::fromFeetAndInches(5, 11),
        hometown: 'Test City',
        weight: 200,
        signature_move: 'Test Move'
    );

    $result = CreateAction::run($data);

    expect($result->height)->toBeInstanceOf(Height::class);
    expect($result->height->feet)->toBe(5);
    expect($result->height->inches)->toBe(11);
    expect($result->height->totalInches())->toBe(71); // 5*12 + 11
});
