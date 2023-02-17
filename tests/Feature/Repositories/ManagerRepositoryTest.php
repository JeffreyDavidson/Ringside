<?php

use App\Data\ManagerData;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Manager;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;

test('creates a manager', function () {
    $data = new ManagerData('Taylor', 'Otwell', null);

    (new ManagerRepository())->create($data);

    expect(Manager::latest()->first())
        ->first_name->toEqual('Taylor')
        ->last_name->toEqual('Otwell');
});

test('it updates a manager', function () {
    $manager = Manager::factory()->create();
    $data = new ManagerData('Taylor', 'Otwell', null);

    (new ManagerRepository())->update($manager, $data);

    expect($manager->fresh())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell');
});

test('it deletes a manager', function () {
    $manager = Manager::factory()->create();

    (new ManagerRepository())->delete($manager);

    expect($manager)
        ->deleted_at->not()->toBeNull();
});

test('it can restore a trashed manager', function () {
    $manager = Manager::factory()->trashed()->create();

    (new ManagerRepository())->restore($manager);

    expect($manager->fresh())
        ->deleted_at->toBeNull();
});

test('it can employ manager', function () {
    $manager = Manager::factory()->create();

    (new ManagerRepository())->employ($manager, Carbon::now());

    expect($manager->fresh())
        ->employments->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual(Carbon::now()->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can update a managers employment', function () {
    $manager = Manager::factory()
        ->has(Employment::factory(1, ['started_at' => Carbon::now()->addDays(3)]))
        ->create();

    $date = Carbon::now();

    (new ManagerRepository())->employ($manager, $date);

    expect($manager->fresh())
        ->employments->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can release a manager', function () {
    $manager = Manager::factory()
        ->has(Employment::factory(1, ['started_at' => Carbon::now()->subDays(3)]))
        ->create();

    $date = Carbon::now();

    (new ManagerRepository())->release($manager, $date);

    expect($manager->fresh()->employments)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(3)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can injure a manager', function () {
    $date = Carbon::now();

    $manager = Manager::factory()->create();

    (new ManagerRepository())->injure($manager, $date);

    expect($manager->fresh()->injuries)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can clear an injury of a manager', function () {
    $manager = Manager::factory()
        ->has(Injury::factory(1, ['started_at' => Carbon::now()->subDays(3)]))
        ->create();

    $date = Carbon::now();

    (new ManagerRepository())->clearInjury($manager, $date);

    expect($manager->fresh()->injuries)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(3)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can retire a manager', function () {
    $date = Carbon::now();

    $manager = Manager::factory()->create();

    (new ManagerRepository())->retire($manager, $date);

    expect($manager->fresh()->retirements)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can unretire a retired manager', function () {
    $date = Carbon::now();

    $manager = Manager::factory()
        ->has(Retirement::factory(1, ['started_at' => $date->copy()->subDays(2)]))
        ->create();

    (new ManagerRepository())->unretire($manager, $date);

    expect($manager->fresh()->retirements)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(2)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can suspend a manager', function () {
    $date = Carbon::now();

    $manager = Manager::factory()->create();

    (new ManagerRepository())->suspend($manager, $date);

    expect($manager->fresh()->suspensions)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can reinstate a suspended manager', function () {
    $date = Carbon::now();

    $manager = Manager::factory()
        ->has(Suspension::factory(1, ['started_at' => $date->copy()->subDays(2)]))
        ->create();

    (new ManagerRepository())->reinstate($manager, $date);

    expect($manager->fresh()->suspensions)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(2)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can update a future employment for a manager', function () {
    $date = Carbon::now();

    $manager = Manager::factory()
        ->has(Employment::factory(1, ['started_at' => $date->copy()->addDays(2)]))
        ->create();

    (new ManagerRepository())->updateEmployment($manager, $date);

    expect($manager->fresh()->employments)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can disassociate a manager from its current tag teams', function () {
    $date = Carbon::now();

    $manager = Manager::factory()
        ->hasAttached(TagTeam::factory()->count(2), ['hired_at' => $date->copy()->subDays(3)])
        ->create();

    (new ManagerRepository())->removeFromCurrentTagTeams($manager);

    expect($manager->fresh()->currentTagTeams)
        ->toHaveCount(0);

    expect($manager->fresh()->previousTagTeams)
        ->toHaveCount(2)
        ->each(fn ($tagTeam) => $tagTeam->pivot->left_at->toEqual($date));
});

test('it can disassociate a manager from its current wrestlers', function () {
    $date = Carbon::now();

    $manager = Manager::factory()
        ->hasAttached(Wrestler::factory()->count(2), ['hired_at' => $date->copy()->subDays(3)])
        ->create();

    (new ManagerRepository())->removeFromCurrentWrestlers($manager);

    expect($manager->fresh()->currentWrestlers)
        ->toHaveCount(0);

    expect($manager->fresh()->previousWrestlers)
        ->toHaveCount(2)
        ->each(fn ($wrestler) => $wrestler->pivot->left_at->toEqual($date));
});
