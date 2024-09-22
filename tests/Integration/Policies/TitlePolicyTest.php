<?php

declare(strict_types=1);

use App\Models\Title;
use App\Policies\TitlePolicy;

test('it authorizes a user can create a title', function () {
    expect((new TitlePolicy)->create(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->create(basicUser()))->toBeFalsy();
});

test('it authorizes a user can update a title', function () {
    expect((new TitlePolicy)->update(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->update(basicUser()))->toBeFalsy();
});

test('it authorizes a user can delete a title', function () {
    expect((new TitlePolicy)->delete(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->delete(basicUser()))->toBeFalsy();
});

test('it authorizes a user can restore a title', function () {
    expect((new TitlePolicy)->restore(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->restore(basicUser()))->toBeFalsy();
});

test('it authorizes a user can retire a title', function () {
    expect((new TitlePolicy)->retire(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->retire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can unretire a title', function () {
    expect((new TitlePolicy)->unretire(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->unretire(basicUser()))->toBeFalsy();
});

test('it authorizes a user can employ of a title', function () {
    expect((new TitlePolicy)->activate(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->activate(basicUser()))->toBeFalsy();
});

test('it authorizes a user can release of a title', function () {
    expect((new TitlePolicy)->deactivate(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->deactivate(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view the listing of titles', function () {
    expect((new TitlePolicy)->viewList(administrator()))->toBeTruthy();
    expect((new TitlePolicy)->viewList(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view a title profile', function () {
    expect((new TitlePolicy)->view(administrator(), Title::factory()->create()))->toBeTruthy();
    expect((new TitlePolicy)->view(basicUser(), Title::factory()->create()))->toBeFalsy();
});
