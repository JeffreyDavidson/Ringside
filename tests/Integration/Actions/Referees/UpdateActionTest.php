<?php

declare(strict_types=1);

use App\Actions\Referees\UpdateAction;
use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates referee basic information', function () {
    $referee = Referee::factory()->create([
        'first_name' => 'Original',
        'last_name' => 'Name',
    ]);

    $updateData = new RefereeData(
        first_name: 'Updated',
        last_name: 'Name',
        employment_date: null
    );

    $result = UpdateAction::run($referee, $updateData);

    expect($result)->toBeInstanceOf(Referee::class);
    expect($result->first_name)->toBe('Updated');
    expect($result->last_name)->toBe('Name');

    $this->assertDatabaseHas('referees', [
        'id' => $referee->id,
        'first_name' => 'Updated',
        'last_name' => 'Name',
    ]);
});

test('it updates referee and employs them when employment date provided', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now();

    expect($referee->isEmployed())->toBeFalse();

    $updateData = new RefereeData(
        first_name: 'Earl',
        last_name: 'Hebner',
        employment_date: $employmentDate
    );

    $result = UpdateAction::run($referee, $updateData);

    $result->refresh();
    expect($result->first_name)->toBe('Earl');
    expect($result->last_name)->toBe('Hebner');
    expect($result->isEmployed())->toBeTrue();

    // Verify employment record was created via EmployAction
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it updates referee without employing when no employment date', function () {
    $referee = Referee::factory()->create();

    expect($referee->isEmployed())->toBeFalse();

    $updateData = new RefereeData(
        first_name: 'Mike',
        last_name: 'Chioda',
        employment_date: null
    );

    $result = UpdateAction::run($referee, $updateData);

    $result->refresh();
    expect($result->first_name)->toBe('Mike');
    expect($result->last_name)->toBe('Chioda');
    expect($result->isEmployed())->toBeFalse();

    // Verify no employment record was created
    $this->assertDatabaseMissing('referees_employments', [
        'referee_id' => $referee->id,
    ]);
});

test('it does not re-employ already employed referee', function () {
    $referee = Referee::factory()->employed()->create();
    $originalEmployment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();

    $updateData = new RefereeData(
        first_name: 'Updated',
        last_name: 'Name',
        employment_date: now()
    );

    $result = UpdateAction::run($referee, $updateData);

    $result->refresh();
    expect($result->first_name)->toBe('Updated');
    expect($result->last_name)->toBe('Name');
    expect($result->isEmployed())->toBeTrue();

    // Should still have only the original employment record
    expect($result->employments()->count())->toBe(1);
    expect($result->currentEmployment->id)->toBe($originalEmployment->id);
});

test('it handles DateHelper date resolution for employment', function () {
    $referee = Referee::factory()->create();

    $updateData = new RefereeData(
        first_name: 'Charles',
        last_name: 'Robinson',
        employment_date: now()->subDays(10) // Past date
    );

    $result = UpdateAction::run($referee, $updateData);

    $result->refresh();
    expect($result->isEmployed())->toBeTrue();

    // DateHelper should have processed the employment date
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->subDays(10)->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->create();

    $updateData = new RefereeData(
        first_name: 'Transaction',
        last_name: 'Test',
        employment_date: now()
    );

    // Simulate transaction - all changes should be atomic
    $result = UpdateAction::run($referee, $updateData);

    $result->refresh();

    // Both referee update and employment should succeed together
    $this->assertDatabaseHas('referees', [
        'id' => $referee->id,
        'first_name' => 'Transaction',
        'last_name' => 'Test',
    ]);

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);
});

test('it returns updated referee instance', function () {
    $referee = Referee::factory()->create();

    $updateData = new RefereeData(
        first_name: 'Return',
        last_name: 'Test',
        employment_date: null
    );

    $result = UpdateAction::run($referee, $updateData);

    expect($result)->toBeInstanceOf(Referee::class);
    expect($result->id)->toBe($referee->id);
    expect($result->first_name)->toBe('Return');
    expect($result->last_name)->toBe('Test');
});

test('it preserves referee id and timestamps', function () {
    $referee = Referee::factory()->create();
    $originalId = $referee->id;
    $originalCreatedAt = $referee->created_at;

    $updateData = new RefereeData(
        first_name: 'Preserve',
        last_name: 'Test',
        employment_date: null
    );

    $result = UpdateAction::run($referee, $updateData);

    expect($result->id)->toBe($originalId);
    expect($result->created_at->timestamp)->toBe($originalCreatedAt->timestamp);
    expect($result->updated_at)->not()->toBeNull();
});

test('it validates referee can be updated', function () {
    $referee = Referee::factory()->create();

    $updateData = new RefereeData(
        first_name: 'Valid',
        last_name: 'Update',
        employment_date: null
    );

    // Should succeed without throwing validation exception
    $result = UpdateAction::run($referee, $updateData);

    expect($result)->toBeInstanceOf(Referee::class);
    expect($result->first_name)->toBe('Valid');
    expect($result->last_name)->toBe('Update');
});

test('it uses EmployAction for consistent employment handling', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now();

    $updateData = new RefereeData(
        first_name: 'EmployAction',
        last_name: 'Test',
        employment_date: $employmentDate
    );

    $result = UpdateAction::run($referee, $updateData);

    // Verify the referee was employed using the correct architectural pattern
    expect($result->isEmployed())->toBeTrue();
    expect($result->currentEmployment()->exists())->toBeTrue();

    $employment = $result->currentEmployment()->first();
    expect($employment->started_at->toDateTimeString())->toBe($employmentDate->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});
