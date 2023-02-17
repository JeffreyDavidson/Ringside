<?php

use App\Data\RefereeData;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Referee;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

test('creates a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);

    (new RefereeRepository())->create($data);

    expect(Referee::latest()->first())
        ->first_name->toEqual('Taylor')
        ->last_name->toEqual('Otwell');
});

test('it updates a referee', function () {
    $referee = Referee::factory()->create();
    $data = new RefereeData('Taylor', 'Otwell', null);

    (new RefereeRepository())->update($referee, $data);

    expect($referee->fresh())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell');
});

test('it deletes a referee', function () {
    $referee = Referee::factory()->create();

    (new RefereeRepository())->delete($referee);

    expect($referee)
        ->deleted_at->not()->toBeNull();
});

test('it can restore a trashed referee', function () {
    $referee = Referee::factory()->trashed()->create();

    (new RefereeRepository())->restore($referee);

    expect($referee->fresh())
        ->deleted_at->toBeNull();
});

test('it can employ referee', function () {
    $referee = Referee::factory()->create();

    (new RefereeRepository())->employ($referee, Carbon::now());

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual(Carbon::now()->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can update a referees employment', function () {
    $referee = Referee::factory()
        ->has(Employment::factory(1, ['started_at' => Carbon::now()->addDays(3)]))
        ->create();

    $date = Carbon::now();

    (new RefereeRepository())->employ($referee, $date);

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can release a referee', function () {
    $referee = Referee::factory()
        ->has(Employment::factory(1, ['started_at' => Carbon::now()->subDays(3)]))
        ->create();

    $date = Carbon::now();

    (new RefereeRepository())->release($referee, $date);

    expect($referee->fresh()->employments)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(3)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can injure a referee', function () {
    $date = Carbon::now();

    $referee = Referee::factory()->create();

    (new RefereeRepository())->injure($referee, $date);

    expect($referee->fresh()->injuries)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can clear an injury of a referee', function () {
    $referee = Referee::factory()
        ->has(Injury::factory(1, ['started_at' => Carbon::now()->subDays(3)]))
        ->create();

    $date = Carbon::now();

    (new RefereeRepository())->clearInjury($referee, $date);

    expect($referee->fresh()->injuries)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(3)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can retire a referee', function () {
    $date = Carbon::now();

    $referee = Referee::factory()->create();

    (new RefereeRepository())->retire($referee, $date);

    expect($referee->fresh()->retirements)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can unretire a retired referee', function () {
    $date = Carbon::now();

    $referee = Referee::factory()
        ->has(Retirement::factory(1, ['started_at' => $date->copy()->subDays(2)]))
        ->create();

    (new RefereeRepository())->unretire($referee, $date);

    expect($referee->fresh()->retirements)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(2)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can suspend a referee', function () {
    $date = Carbon::now();

    $referee = Referee::factory()->create();

    (new RefereeRepository())->suspend($referee, $date);

    expect($referee->fresh()->suspensions)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});

test('it can reinstate a suspended referee', function () {
    $date = Carbon::now();

    $referee = Referee::factory()
        ->has(Suspension::factory(1, ['started_at' => $date->copy()->subDays(2)]))
        ->create();

    (new RefereeRepository())->reinstate($referee, $date);

    expect($referee->fresh()->suspensions)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->copy()->subDays(2)->toDateTimeString())
        ->first()->ended_at->toDateTimeString()->toEqual($date->toDateTimeString());
});

test('it can update a future employment for a referee', function () {
    $date = Carbon::now();

    $referee = Referee::factory()
        ->has(Employment::factory(1, ['started_at' => $date->copy()->addDays(2)]))
        ->create();

    (new RefereeRepository())->updateEmployment($referee, $date);

    expect($referee->fresh()->employments)
        ->toHaveCount(1)
        ->first()->started_at->toDateTimeString()->toEqual($date->toDateTimeString())
        ->first()->ended_at->toBeNull();
});
