<?php

declare(strict_types=1);

use App\Actions\Referees\CreateAction;
use App\Data\Referees\RefereeData;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it creates a referee with basic information', function () {
    $data = new RefereeData(
        first_name: 'Earl',
        last_name: 'Hebner',
        employment_date: null
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Referee::class);
    expect($result->first_name)->toBe('Earl');
    expect($result->last_name)->toBe('Hebner');

    $this->assertDatabaseHas('referees', [
        'first_name' => 'Earl',
        'last_name' => 'Hebner',
    ]);

    // Should not create employment record when no employment date provided
    $this->assertDatabaseMissing('employments', [
        'employable_id' => $result->id,
    ]);
});

test('it creates a referee with employment when employment date is provided', function () {
    $employmentDate = now();

    $data = new RefereeData(
        first_name: 'Mike',
        last_name: 'Chioda',
        employment_date: $employmentDate
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result->first_name)->toBe('Mike');
    expect($result->last_name)->toBe('Chioda');
    expect($result->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('referees', [
        'first_name' => 'Mike',
        'last_name' => 'Chioda',
    ]);

    // Should create employment record using EmployAction
    $this->assertDatabaseHas('employments', [
        'employable_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it creates referee with proper database transactions', function () {
    $employmentDate = now();

    $data = new RefereeData(
        first_name: 'Charles',
        last_name: 'Robinson',
        employment_date: $employmentDate
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Referee::class);
    expect($result->first_name)->toBe('Charles');
    expect($result->last_name)->toBe('Robinson');

    // Verify database state is consistent
    $this->assertDatabaseHas('referees', [
        'id' => $result->id,
        'first_name' => 'Charles',
        'last_name' => 'Robinson',
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles employment creation through EmployAction dependency injection', function () {
    $employmentDate = now();

    $data = new RefereeData(
        first_name: 'Dave',
        last_name: 'Hebner',
        employment_date: $employmentDate
    );

    $result = resolve(CreateAction::class)->handle($data);

    // Verify the referee was created and employed using the correct architectural pattern
    expect($result->isEmployed())->toBeTrue();
    expect($result->currentEmployment()->exists())->toBeTrue();

    $employment = $result->currentEmployment()->firstOrFail();
    expect(requiredDate($employment->started_at)->toDateTimeString())->toBe($employmentDate->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});
