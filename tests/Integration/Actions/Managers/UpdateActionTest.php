<?php

declare(strict_types=1);

use App\Actions\Managers\UpdateAction;
use App\Data\Managers\ManagerData;
use App\Models\Roster\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates a manager with new information', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'Original',
        'last_name' => 'Name',
    ]);

    $updateData = new ManagerData('Updated', 'Manager', null);

    $result = resolve(UpdateAction::class)->handle($manager, $updateData);

    expect($result)->toBeInstanceOf(Manager::class);
    expect($result->id)->toBe($manager->id);
    expect($result->first_name)->toBe('Updated');
    expect($result->last_name)->toBe('Manager');

    $this->assertDatabaseHas('managers', [
        'id' => $manager->id,
        'first_name' => 'Updated',
        'last_name' => 'Manager',
    ]);
});

test('it updates using the current persisted manager state', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'Original',
        'last_name' => 'Name',
    ]);
    $staleManager = $manager->replicate(['id']);
    $staleManager->id = $manager->id;
    $staleManager->exists = true;

    $updatedManager = resolve(UpdateAction::class)->handle(
        $staleManager,
        new ManagerData('Updated', 'From Stale State', null),
    );

    expect($updatedManager->first_name)->toBe('Updated')
        ->and(Manager::query()->findOrFail($manager->id)->last_name)->toBe('From Stale State');
});

test('it updates manager and creates employment when employment date is provided', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $employmentDate = now();
    $updateData = new ManagerData('John', 'Updated', $employmentDate);

    $result = resolve(UpdateAction::class)->handle($manager, $updateData);

    expect($result->last_name)->toBe('Updated');
    expect($result->currentEmployment()->exists())->toBeTrue();

    $this->assertDatabaseHas('managers', [
        'id' => $manager->id,
        'first_name' => 'John',
        'last_name' => 'Updated',
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it updates manager without affecting existing employment', function () {
    $manager = Manager::factory()->employed()->create([
        'first_name' => 'Employed',
        'last_name' => 'Manager',
    ]);

    expect($manager->currentEmployment()->exists())->toBeTrue();

    $updateData = new ManagerData('Still', 'Employed', null);

    $result = resolve(UpdateAction::class)->handle($manager, $updateData);

    expect($result->first_name)->toBe('Still');
    expect($result->last_name)->toBe('Employed');
    expect($result->currentEmployment()->exists())->toBeTrue(); // Should still be employed

    $this->assertDatabaseHas('managers', [
        'id' => $manager->id,
        'first_name' => 'Still',
        'last_name' => 'Employed',
    ]);
});
