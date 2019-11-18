<?php

namespace Tests\Feature\Generic\Managers;

use App\Models\Manager;
use App\Exceptions\CannotBeInjuredException;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 * @group roster
 */
class InjureManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_injured_manager_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->injureRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_manager_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('pending-employment')->create();

        $response = $this->injureRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->injureRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_manager_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->injureRequest($manager);

        $response->assertForbidden();
    }
}
