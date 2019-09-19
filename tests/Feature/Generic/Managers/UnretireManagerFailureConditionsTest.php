<?php

namespace Tests\Feature\Generic\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 * @group roster
 */
class UnretireManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_manager_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->unretireRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->unretireRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_manager_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->unretireRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_manager_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('pending-employment')->create();

        $response = $this->unretireRequest($manager);

        $response->assertForbidden();
    }
}
