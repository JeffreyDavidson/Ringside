<?php

declare(strict_types=1);

use App\Data\RefereeData;
use App\Models\Referee;
use App\Models\RefereeEmployment;
use App\Repositories\RefereeRepository;

test('creates a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);

    $referee = app(RefereeRepository::class)->create($data);

    expect($referee)
        ->first_name->toEqual('Taylor')
        ->last_name->toEqual('Otwell');
});

test('it updates a referee', function () {
    $referee = Referee::factory()->create();
    $data = new RefereeData('Taylor', 'Otwell', null);

    $referee = app(RefereeRepository::class)->update($referee, $data);

    expect($referee->fresh())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell');
});

test('deletes a referee', function () {
    $referee = Referee::factory()->create();

    app(RefereeRepository::class)->delete($referee);

    expect($referee)
        ->deleted_at->not()->toBeNull();
});

test('restore a trashed referee', function () {
    $referee = Referee::factory()->trashed()->create();

    app(RefereeRepository::class)->restore($referee);

    expect($referee->fresh())
        ->deleted_at->toBeNull();
});

test('employ a referee', function () {
    $referee = Referee::factory()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->employ($referee, $datetime);

    expect($referee->fresh())->employments->toHaveCount(1);
    expect($referee->fresh()->employments->first())->started_at->eq($datetime);
});

test('updates employment of a referee', function () {
    $datetime = now();
    $referee = Referee::factory()
        ->has(RefereeEmployment::factory()->started($datetime->copy()->addDays(2)), 'employments')
        ->create();

    expect($referee->fresh())->employments->toHaveCount(1);
    expect($referee->fresh()->employments->first())
        ->started_at->toDateTimeString()->toEqual($datetime->copy()->addDays(2)->toDateTimeString());

    $referee = app(RefereeRepository::class)->employ($referee, $datetime);

    expect($referee->fresh())->employments->toHaveCount(1);
    expect($referee->fresh()->employments->first())->started_at->eq($datetime);
});

test('release a referee', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->release($referee, $datetime);

    expect($referee->fresh())->employments->toHaveCount(1);
    expect($referee->fresh()->employments->first())->ended_at->eq($datetime);
});

test('injure a referee', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->injure($referee, $datetime);

    expect($referee->fresh())->injuries->toHaveCount(1);
    expect($referee->fresh()->injuries->first())->started_at->eq($datetime);
});

test('clear an injured referee', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->clearInjury($referee, $datetime);

    expect($referee->fresh())->injuries->toHaveCount(1);
    expect($referee->fresh()->injuries->first())->ended_at->eq($datetime);
});

test('retire a referee', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->retire($referee, $datetime);

    expect($referee->fresh())->retirements->toHaveCount(1);
    expect($referee->fresh()->retirements->first())->started_at->eq($datetime);
});

test('unretire a referee', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->unretire($referee, $datetime);

    expect($referee->fresh())->retirements->toHaveCount(1);
    expect($referee->fresh()->retirements->first())->ended_at->eq($datetime);
});

test('suspend a referee', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->suspend($referee, $datetime);

    expect($referee->fresh())->suspensions->toHaveCount(1);
    expect($referee->fresh()->suspensions->first())->started_at->eq($datetime);
});

test('reinstate a referee', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $referee = app(RefereeRepository::class)->reinstate($referee, $datetime);

    expect($referee->fresh())->suspensions->toHaveCount(1);
    expect($referee->fresh()->suspensions->first())->ended_at->eq($datetime);
});
