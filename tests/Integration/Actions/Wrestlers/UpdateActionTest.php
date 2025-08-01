<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UpdateAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates wrestler basic information', function () {
    $wrestler = Wrestler::factory()->create([
        'name' => 'Original Name',
        'height' => 70,
        'weight' => 200,
        'hometown' => 'Original Town',
        'signature_move' => 'Original Move',
    ]);

    $updateData = new WrestlerData(
        name: 'Updated Name',
        height: 75,
        weight: 250,
        hometown: 'Updated Town',
        signature_move: 'Updated Move',
        employment_date: null,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    expect($result)->toBeInstanceOf(Wrestler::class);
    expect($result->name)->toBe('Updated Name');
    expect($result->height->toInches())->toBe(75);
    expect($result->weight)->toBe(250);
    expect($result->hometown)->toBe('Updated Town');
    expect($result->signature_move)->toBe('Updated Move');

    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'name' => 'Updated Name',
        'height' => 75,
        'weight' => 250,
        'hometown' => 'Updated Town',
        'signature_move' => 'Updated Move',
    ]);
});

test('it updates wrestler and employs them when employment date provided', function () {
    $wrestler = Wrestler::factory()->create();
    $employmentDate = now();

    expect($wrestler->isEmployed())->toBeFalse();

    $updateData = new WrestlerData(
        name: 'John Cena',
        height: 73,
        weight: 251,
        hometown: 'West Newbury, MA',
        signature_move: 'Attitude Adjustment',
        employment_date: $employmentDate,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    $result->refresh();
    expect($result->name)->toBe('John Cena');
    expect($result->isEmployed())->toBeTrue();

    // Verify employment record was created via EmployAction
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it updates wrestler without employing when no employment date', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler->isEmployed())->toBeFalse();

    $updateData = new WrestlerData(
        name: 'The Rock',
        height: 77,
        weight: 260,
        hometown: 'Miami, FL',
        signature_move: 'Rock Bottom',
        employment_date: null,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    $result->refresh();
    expect($result->name)->toBe('The Rock');
    expect($result->isEmployed())->toBeFalse();

    // Verify no employment record was created
    $this->assertDatabaseMissing('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
    ]);
});

test('it does not re-employ already employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $originalEmployment = $wrestler->currentEmployment;

    expect($wrestler->isEmployed())->toBeTrue();

    $updateData = new WrestlerData(
        name: 'Updated Name',
        height: 72,
        weight: 220,
        hometown: 'Updated Town',
        signature_move: 'Updated Move',
        employment_date: now(),
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    $result->refresh();
    expect($result->name)->toBe('Updated Name');
    expect($result->isEmployed())->toBeTrue();

    // Should still have only the original employment record
    expect($result->employments()->count())->toBe(1);
    expect($result->currentEmployment->id)->toBe($originalEmployment->id);
});

test('it employs managers when wrestler gets employed', function () {
    $wrestler = Wrestler::factory()->create();
    $manager1 = Manager::factory()->create(); // unemployed
    $manager2 = Manager::factory()->employed()->create(); // already employed

    // Assign managers to wrestler
    $wrestler->managers()->attach($manager1->id, ['hired_at' => now()->subDays(5)]);
    $wrestler->managers()->attach($manager2->id, ['hired_at' => now()->subDays(3)]);

    expect($wrestler->isEmployed())->toBeFalse();
    expect($manager1->isEmployed())->toBeFalse();
    expect($manager2->isEmployed())->toBeTrue();

    $employmentDate = now();
    $updateData = new WrestlerData(
        name: 'Updated Name',
        height: 74,
        weight: 230,
        hometown: 'Updated Town',
        signature_move: 'Updated Move',
        employment_date: $employmentDate,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    $result->refresh();
    $manager1->refresh();
    $manager2->refresh();

    expect($result->isEmployed())->toBeTrue();
    expect($manager1->isEmployed())->toBeTrue(); // Should now be employed via cascade
    expect($manager2->isEmployed())->toBeTrue(); // Should remain employed

    // Both wrestler and manager1 should have new employment records
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager1->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution for employment', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Test Name',
        height: 70,
        weight: 200,
        hometown: 'Test Town',
        signature_move: 'Test Move',
        employment_date: now()->subDays(10), // Past date
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    $result->refresh();
    expect($result->isEmployed())->toBeTrue();

    // DateHelper should have processed the employment date
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(10)->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it maintains transaction boundaries', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Transaction Test',
        height: 71,
        weight: 210,
        hometown: 'Transaction Town',
        signature_move: 'Transaction Move',
        employment_date: now(),
        managers: null
    );

    // Simulate transaction - all changes should be atomic
    $result = UpdateAction::run($wrestler, $updateData);

    $result->refresh();

    // Both wrestler update and employment should succeed together
    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'name' => 'Transaction Test',
    ]);

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);
});

test('it returns updated wrestler instance', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Return Test',
        height: 76,
        weight: 240,
        hometown: 'Return Town',
        signature_move: 'Return Move',
        employment_date: null,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    expect($result)->toBeInstanceOf(Wrestler::class);
    expect($result->id)->toBe($wrestler->id);
    expect($result->name)->toBe('Return Test');
    expect($result->height->toInches())->toBe(76);
    expect($result->weight)->toBe(240);
});

test('it handles height conversion correctly', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Height Test',
        height: 71, // 5'11"
        weight: 200,
        hometown: 'Height Town',
        signature_move: 'Height Move',
        employment_date: null,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    expect($result->height->feet)->toBe(5);
    expect($result->height->inches)->toBe(11);
    expect($result->height->toInches())->toBe(71);
});

test('it preserves wrestler id and timestamps', function () {
    $wrestler = Wrestler::factory()->create();
    $originalId = $wrestler->id;
    $originalCreatedAt = $wrestler->created_at;

    $updateData = new WrestlerData(
        name: 'Preserve Test',
        height: 72,
        weight: 205,
        hometown: 'Preserve Town',
        signature_move: 'Preserve Move',
        employment_date: null,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    expect($result->id)->toBe($originalId);
    expect($result->created_at->timestamp)->toBe($originalCreatedAt->timestamp);
    expect($result->updated_at->timestamp)->toBeGreaterThan($originalCreatedAt->timestamp);
});

test('it handles null signature move', function () {
    $wrestler = Wrestler::factory()->create(['signature_move' => 'Original Move']);

    $updateData = new WrestlerData(
        name: 'Null Move Test',
        height: 70,
        weight: 200,
        hometown: 'Null Town',
        signature_move: null,
        employment_date: null,
        managers: null
    );

    $result = UpdateAction::run($wrestler, $updateData);

    expect($result->signature_move)->toBeNull();

    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'signature_move' => null,
    ]);
});
