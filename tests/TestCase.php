<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function actAs($states = [], $attributes = [])
    {
        $user = ($states instanceof User) ? $states : factory(User::class)->states($states)->create($attributes);
        $this->actingAs($user);

        return $user;
    }
}
