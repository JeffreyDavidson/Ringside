<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('data', function ($key) {
            return $this->original->getData()[$key];
        });
    }

    public function actAs($states = [], $attributes = [])
    {
        $user = ($states instanceof User) ? $states : factory(User::class)->states($states)->create($attributes);
        $this->actingAs($user);

        return $user;
    }
}
