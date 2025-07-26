<?php

declare(strict_types=1);

use App\Actions\Managers\CreateAction;
use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it creates a manager with basic information', function () {
    $data = new ManagerData('Taylor', 'Otwell', null);

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Manager::class);
    expect($result->first_name)->toBe('Taylor');
    expect($result->last_name)->toBe('Otwell');
    
    $this->assertDatabaseHas('managers', [
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
    ]);
    
    // Should not create employment record when no employment date provided
    $this->assertDatabaseMissing('managers_employments', [
        'manager_id' => $result->id,
    ]);
});

test('it creates a manager with employment when employment date is provided', function () {
    $employmentDate = now();
    $data = new ManagerData('Jeffrey', 'Davidson', $employmentDate);

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Manager::class);
    expect($result->first_name)->toBe('Jeffrey');
    expect($result->last_name)->toBe('Davidson');
    
    $this->assertDatabaseHas('managers', [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
    ]);
    
    // Should create employment record
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
    
    // Manager should be marked as employed
    expect($result->fresh()->isEmployed())->toBeTrue();
});

test('it creates manager with all optional fields', function () {
    $employmentDate = now();
    $data = new ManagerData(
        first_name: 'John', 
        last_name: 'Doe',
        employment_date: $employmentDate
    );

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Manager::class);
    expect($result->first_name)->toBe('John');
    expect($result->last_name)->toBe('Doe');
    
    // Verify database state
    $this->assertDatabaseHas('managers', [
        'id' => $result->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});