<?php

declare(strict_types=1);

use App\Models\Referee;
use App\Policies\RefereePolicy;

test('it authorizes a user can create a referee', function () {
    expect((new RefereePolicy)->create(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->create(basicUser()))->toBeFalsy();
});

test('it authorizes a user can update a referee', function () {
    expect((new RefereePolicy)->update(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->update(basicUser()))->toBeFalsy();
});

test('it authorizes a user can delete a referee', function () {
    expect((new RefereePolicy)->delete(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->delete(basicUser()))->toBeFalsy();
});

test('it authorizes a user can restore a referee', function () {
    expect((new RefereePolicy)->restore(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->restore(basicUser()))->toBeFalsy();
});

test('it authorizes a user can retire a referee', function () {
    expect((new RefereePolicy)->retire(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->retire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can unretire a referee', function () {
    expect((new RefereePolicy)->unretire(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->unretire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can suspend a referee', function () {
    expect((new RefereePolicy)->suspend(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->suspend(basicUser()))->toBeFalsy();
});

test('it authorizes a user can reinstate a referee', function () {
    expect((new RefereePolicy)->reinstate(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->reinstate(basicUser()))->toBeFalsy();
});

test('it authorizes a user can injure a referee', function () {
    expect((new RefereePolicy)->injure(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->injure(basicUser()))->toBeFalsy();
});

test('it authorizes a user can clear an injury of a referee', function () {
    expect((new RefereePolicy)->clearFromInjury(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->clearFromInjury(basicUser()))->toBeFalsy();
});

test('it authorizes a user can employ of a referee', function () {
    expect((new RefereePolicy)->employ(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->employ(basicUser()))->toBeFalsy();
});

test('it authorizes a user can release of a referee', function () {
    expect((new RefereePolicy)->release(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->release(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view the listing of referees', function () {
    expect((new RefereePolicy)->viewList(administrator()))->toBeTruthy();
    expect((new RefereePolicy)->viewList(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view a referee profile', function () {
    expect((new RefereePolicy)->view(administrator(), Referee::factory()->create()))->toBeTruthy();
    expect((new RefereePolicy)->view(basicUser(), Referee::factory()->create()))->toBeFalsy();
});
