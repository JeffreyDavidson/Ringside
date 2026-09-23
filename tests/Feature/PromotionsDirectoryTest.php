<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;

it('lets platform administrators create and edit promotions from the directory', function () {
    $promotion = Promotion::factory()->create(['name' => 'Ringside Wrestling']);

    actingAs(User::factory()->administrator()->create())
        ->get(route('promotions.index'))
        ->assertOk()
        ->assertSee('Ringside Wrestling')
        ->assertSee(__('promotions.create'))
        ->assertSee(__('promotions.edit'));
});

it('keeps the platform promotions directory unavailable to regular users', function () {
    actingAs(User::factory()->basicUser()->create())
        ->get(route('promotions.index'))
        ->assertForbidden();
});
