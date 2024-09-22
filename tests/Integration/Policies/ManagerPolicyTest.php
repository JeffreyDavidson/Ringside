<?php

declare(strict_types=1);

use App\Models\Manager;
use App\Policies\ManagerPolicy;

test('it authorizes a user can create a manager', function () {
    expect((new ManagerPolicy)->create(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->create(basicUser()))->toBeFalsy();
});

test('it authorizes a user can update a manager', function () {
    expect((new ManagerPolicy)->update(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->update(basicUser()))->toBeFalsy();
});

test('it authorizes a user can delete a manager', function () {
    expect((new ManagerPolicy)->delete(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->delete(basicUser()))->toBeFalsy();
});

test('it authorizes a user can restore a manager', function () {
    expect((new ManagerPolicy)->restore(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->restore(basicUser()))->toBeFalsy();
});

test('it authorizes a user can retire a manager', function () {
    expect((new ManagerPolicy)->retire(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->retire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can unretire a manager', function () {
    expect((new ManagerPolicy)->unretire(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->unretire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can suspend a manager', function () {
    expect((new ManagerPolicy)->suspend(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->suspend(basicUser()))->toBeFalsy();
});

test('it authorizes a user can reinstate a manager', function () {
    expect((new ManagerPolicy)->reinstate(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->reinstate(basicUser()))->toBeFalsy();
});

test('it authorizes a user can injure a manager', function () {
    expect((new ManagerPolicy)->injure(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->injure(basicUser()))->toBeFalsy();
});

test('it authorizes a user can clear an injury of a manager', function () {
    expect((new ManagerPolicy)->clearFromInjury(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->clearFromInjury(basicUser()))->toBeFalsy();
});

test('it authorizes a user can employ of a manager', function () {
    expect((new ManagerPolicy)->employ(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->employ(basicUser()))->toBeFalsy();
});

test('it authorizes a user can release of a manager', function () {
    expect((new ManagerPolicy)->release(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->release(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view the listing of managers', function () {
    expect((new ManagerPolicy)->viewList(administrator()))->toBeTruthy();
    expect((new ManagerPolicy)->viewList(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view a manager profile', function () {
    expect((new ManagerPolicy)->view(administrator(), Manager::factory()->create()))->toBeTruthy();
    expect((new ManagerPolicy)->view(basicUser(), Manager::factory()->create()))->toBeFalsy();

    $user = basicUser();
    expect((new ManagerPolicy)->view($user, Manager::factory()->for($user)->create()))->toBeTruthy();
});
