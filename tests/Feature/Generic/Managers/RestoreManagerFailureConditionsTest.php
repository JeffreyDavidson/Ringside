<?php

namespace Tests\Feature\Generic\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class RestoreManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_manager_cannot_be_restored()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_introduction_manager_cannot_be_restored()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('pending-introduction')->create();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertNotFound();
    }

    /** @test */
    public function an_injured_manager_cannot_be_restored()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_restored()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_manager_cannot_be_restored()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertNotFound();
    }
}
