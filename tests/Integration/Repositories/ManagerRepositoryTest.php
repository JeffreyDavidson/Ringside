<?php

declare(strict_types=1);

use App\Data\ManagerData;
use App\Models\Employment;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\ManagerRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('creates a manager', function () {
    $data = new ManagerData('Hulk', 'Hogan', null);

    $manager = app(ManagerRepository::class)->create($data);

    expect($manager)
        ->first_name->toEqual('Hulk')
        ->last_name->toEqual('Hogan');
});

test('it updates a manager', function () {
    $manager = Manager::factory()->create();
    $data = new ManagerData('Hulk', 'Hogan', null);

    $manager = app(ManagerRepository::class)->update($manager, $data);

    expect($manager->fresh())
        ->first_name->toBe('Hulk')
        ->last_name->toBe('Hogan');
});

test('it deletes a manager', function () {
    $manager = Manager::factory()->create();

    app(ManagerRepository::class)->delete($manager);

    expect($manager)
        ->deleted_at->not()->toBeNull();
});

test('it can restore a trashed manager', function () {
    $manager = Manager::factory()->trashed()->create();

    app(ManagerRepository::class)->restore($manager);

    expect($manager->fresh())
        ->deleted_at->toBeNull();
});

test('employ a manager', function () {
    $manager = Manager::factory()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->employ($manager, $datetime);

    expect($manager->fresh())->employments->toHaveCount(1);
    expect($manager->fresh()->employments->first())->started_at->eq($datetime);
});

test('updates employment of a manager', function () {
    $datetime = now();
    $manager = Manager::factory()
        ->has(Employment::factory()->started($datetime->copy()->addDays(2)))
        ->create();

    expect($manager->fresh())->employments->toHaveCount(1);
    expect($manager->fresh()->employments->first())
        ->started_at->eq($datetime->copy()->addDays(2));

    $manager = app(ManagerRepository::class)->employ($manager, $datetime);

    expect($manager->fresh())->employments->toHaveCount(1);
    expect($manager->fresh()->employments->first())->started_at->eq($datetime);
});

test('release a manager', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->release($manager, $datetime);

    expect($manager->fresh()->employments)->toHaveCount(1);
    expect($manager->fresh()->employments->first())->started_at->eq($datetime->copy()->subDays(3));
});

test('it can injure a manager', function () {
    $manager = Manager::factory()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->injure($manager, $datetime);

    expect($manager->fresh()->injuries)->toHaveCount(1);
    expect($manager->fresh()->injuries->first())->started_at->eq($datetime);
});

test('clear an injured manager', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->clearInjury($manager, $datetime);

    expect($manager->fresh()->injuries)->toHaveCount(1);
    expect($manager->fresh()->injuries->first())->started_at->eq($datetime->copy()->subDays(3));
});

test('retire a manager', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->retire($manager, $datetime);

    expect($manager->fresh()->retirements)->toHaveCount(1);
    expect($manager->fresh()->retirements->first())->started_at->eq($datetime);
});

test('unretire a manager', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->unretire($manager, $datetime);

    expect($manager->fresh()->retirements)->toHaveCount(1);
    expect($manager->fresh()->retirements()->first())->started_at->eq($datetime->copy()->subDays(2));
});

test('suspend a manager', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->suspend($manager, $datetime);

    expect($manager->fresh()->suspensions)->toHaveCount(1);
    expect($manager->fresh()->suspensions->first())->started_at->eq($datetime);
});

test('reinstate a manager', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $manager = app(ManagerRepository::class)->reinstate($manager, $datetime);

    expect($manager->fresh()->suspensions)->toHaveCount(1);
    expect($manager->fresh()->suspensions()->first())->started_at->eq($datetime->copy()->subDays(2));
});

test('remove a manager from its current tag teams', function () {
    $datetime = now();

    $manager = Manager::factory()
        ->hasAttached(TagTeam::factory()->count(2), ['hired_at' => $datetime->copy()->subDays(3)])
        ->create();

    app(ManagerRepository::class)->removeFromCurrentTagTeams($manager);

    expect($manager->fresh()->currentTagTeams)->toHaveCount(0);
    expect($manager->fresh()->previousTagTeams)->toHaveCount(2);
    expect($manager->fresh()->previousTagTeams)->each(fn ($tagTeam) => $tagTeam->pivot->left_at->eq($datetime));
});

test('it can disassociate a manager from its current wrestlers', function () {
    $datetime = now();

    $manager = Manager::factory()
        ->hasAttached(Wrestler::factory()->count(2), ['hired_at' => $datetime->copy()->subDays(3)])
        ->create();

    app(ManagerRepository::class)->removeFromCurrentWrestlers($manager);

    expect($manager->fresh()->currentWrestlers)->toHaveCount(0);

    expect($manager->fresh()->previousWrestlers)->toHaveCount(2);
    expect($manager->fresh()->previousWrestlers)->each(fn ($wrestler) => $wrestler->pivot->left_at->eq($datetime));
});
