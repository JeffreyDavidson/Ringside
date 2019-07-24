<?php

namespace Tests\Feature\SuperAdmin\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group superadmins
 */
class RetireManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_retire_a_bookable_manager()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_super_administrator_can_retire_an_injured_manager()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->put(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_super_administrator_can_retire_an_suspended_manager()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->put(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->retirement->started_at);
    }
}
