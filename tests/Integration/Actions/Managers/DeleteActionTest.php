<?php

declare(strict_types=1);

use App\Actions\Managers\DeleteAction;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it soft deletes an unemployed manager', function () {
    $manager = Manager::factory()->create();
    
    expect($manager->isEmployed())->toBeFalse();

    DeleteAction::run($manager);

    // Manager should be soft deleted
    $this->assertSoftDeleted('managers', ['id' => $manager->id]);
    
    // Fresh without trashed should return null
    expect(Manager::find($manager->id))->toBeNull();
    
    // Can still find with trashed
    $trashedManager = Manager::withTrashed()->find($manager->id);
    expect($trashedManager)->not->toBeNull();
    expect($trashedManager->deleted_at)->not->toBeNull();
});

test('it soft deletes an employed manager and ends employment', function () {
    $manager = Manager::factory()->employed()->create();
    
    expect($manager->isEmployed())->toBeTrue();

    $deletionDate = now();
    DeleteAction::run($manager, $deletionDate);

    // Manager should be soft deleted
    $this->assertSoftDeleted('managers', ['id' => $manager->id]);
    
    // Employment should be ended
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => $deletionDate->toDateTimeString(),
    ]);
});

test('it ends manager relationships with wrestlers when deleted', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    
    // Create manager-wrestler relationship
    $hiredDate = now()->subDays(30);
    $wrestler->managers()->attach($manager->id, [
        'hired_at' => $hiredDate,
        'fired_at' => null,
    ]);
    
    // Verify relationship exists before deletion
    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'fired_at' => null,
    ]);

    $deletionDate = now();
    DeleteAction::run($manager, $deletionDate);

    // Manager should be soft deleted
    $this->assertSoftDeleted('managers', ['id' => $manager->id]);

    // Manager-wrestler relationship should be ended
    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $hiredDate->toDateTimeString(),
        'fired_at' => $deletionDate->toDateTimeString(),
    ]);
});

test('it ends manager relationships with tag teams when deleted', function () {
    $manager = Manager::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->employed()->create();
    
    // Create manager-tag team relationship
    $hiredDate = now()->subDays(30);
    $tagTeam->managers()->attach($manager->id, [
        'hired_at' => $hiredDate,
        'fired_at' => null,
    ]);
    
    // Verify relationship exists before deletion
    $this->assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'fired_at' => null,
    ]);

    $deletionDate = now();
    DeleteAction::run($manager, $deletionDate);

    // Manager should be soft deleted
    $this->assertSoftDeleted('managers', ['id' => $manager->id]);

    // Manager-tag team relationship should be ended
    $this->assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => $hiredDate->toDateTimeString(),
        'fired_at' => $deletionDate->toDateTimeString(),
    ]);
});

test('it handles deletion with specific date', function () {
    $manager = Manager::factory()->employed()->create();
    $customDeletionDate = now()->subDay();

    DeleteAction::run($manager, $customDeletionDate);

    $trashedManager = Manager::withTrashed()->find($manager->id);
    expect($trashedManager->deleted_at)->not->toBeNull();
    
    // Employment should end on the custom date
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => $customDeletionDate->toDateTimeString(),
    ]);
});