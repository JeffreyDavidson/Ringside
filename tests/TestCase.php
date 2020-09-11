<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use JMac\Testing\Traits\AdditionalAssertions;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication,
        AdditionalAssertions,
        Traits\HasRequests;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('data', function ($key) {
            return $this->original->getData()[$key];
        });
    }

    public function actAs($role = Role::BASIC, $attributes = [])
    {
        $user = User::factory()->withRole($role)->create($attributes);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Assert that the given class uses the provided trait name.
     *
     * @param  string  $trait
     * @param  mixed   $class
     * @return void
     */
    public function assertUsesTrait($trait, $class)
    {
        $this->assertContains($trait, class_uses($class));
    }

    public function assertCollectionHas($collection, $entity)
    {
        if (is_array($entity) || $entity instanceof Collection) {
            foreach ($entity as $test) {
                $this->assertContains($collection, $test);
            }

            return $this;
        }

        $this->assertTrue($collection->contains($entity));

        return $this;
    }

    public function administrators()
    {
        return [
            'administrator' => [Role::ADMINISTRATOR],
            'super_administrator' => [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
