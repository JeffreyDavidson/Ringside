<?php

namespace Tests\Feature\Generic\Manager;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class ActivateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_manager_cannot_be_activated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_manager_cannot_be_activated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_activated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_manager_cannot_be_activated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertForbidden();
    }
}
