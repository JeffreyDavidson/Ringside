<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Spatie\Enum\Phpunit\EnumAssertions;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use EnumAssertions;
    use RefreshDatabase;

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

        // Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }

    public function actAs($role, $attributes = [])
    {
        $user = User::factory()->withRole($role)->create($attributes);

        $this->actingAs($user);

        return $this;
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

    public function assertCollectionDoesntHave($collection, $entity)
    {
        if (is_array($entity) || $entity instanceof Collection) {
            foreach ($entity as $test) {
                $this->assertNotContains($collection, $test);
            }

            return $this;
        }

        $this->assertFalse($collection->contains($entity));

        return $this;
    }
}
