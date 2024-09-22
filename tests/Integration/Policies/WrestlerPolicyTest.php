<?php

declare(strict_types=1);

use App\Models\Wrestler;
use App\Policies\WrestlerPolicy;

test('it authorizes a user can create a wrestler', function () {
    expect((new WrestlerPolicy)->create(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->create(basicUser()))->toBeFalsy();
});

test('it authorizes a user can update a wrestler', function () {
    expect((new WrestlerPolicy)->update(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->update(basicUser()))->toBeFalsy();
});

test('it authorizes a user can delete a wrestler', function () {
    expect((new WrestlerPolicy)->delete(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->delete(basicUser()))->toBeFalsy();
});

test('it authorizes a user can restore a wrestler', function () {
    expect((new WrestlerPolicy)->restore(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->restore(basicUser()))->toBeFalsy();
});

test('it authorizes a user can retire a wrestler', function () {
    expect((new WrestlerPolicy)->retire(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->retire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can unretire a wrestler', function () {
    expect((new WrestlerPolicy)->unretire(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->unretire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can suspend a wrestler', function () {
    expect((new WrestlerPolicy)->suspend(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->suspend(basicUser()))->toBeFalsy();
});

test('it authorizes a user can reinstate a wrestler', function () {
    expect((new WrestlerPolicy)->reinstate(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->reinstate(basicUser()))->toBeFalsy();
});

test('it authorizes a user can injure a wrestler', function () {
    expect((new WrestlerPolicy)->injure(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->injure(basicUser()))->toBeFalsy();
});

test('it authorizes a user can clear an injury of a wrestler', function () {
    expect((new WrestlerPolicy)->clearFromInjury(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->clearFromInjury(basicUser()))->toBeFalsy();
});

test('it authorizes a user can employ of a wrestler', function () {
    expect((new WrestlerPolicy)->employ(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->employ(basicUser()))->toBeFalsy();
});

test('it authorizes a user can release of a wrestler', function () {
    expect((new WrestlerPolicy)->release(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->release(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view the listing of wrestlers', function () {
    expect((new WrestlerPolicy)->viewList(administrator()))->toBeTruthy();
    expect((new WrestlerPolicy)->viewList(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view a wrestler profile', function () {
    expect((new WrestlerPolicy)->view(administrator(), Wrestler::factory()->create()))->toBeTruthy();
    expect((new WrestlerPolicy)->view(basicUser(), Wrestler::factory()->create()))->toBeFalsy();

    $user = basicUser();
    expect((new WrestlerPolicy)->view($user, Wrestler::factory()->for($user)->create()))->toBeTruthy();
});
