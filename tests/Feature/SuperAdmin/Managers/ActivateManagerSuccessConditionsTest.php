<?php

namespace Tests\Feature\SuperAdmin\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group superadmins
 */
class ActivateManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_activate_a_pending_introduction_manager()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('pending-introduction')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertTrue($manager->is_bookable);
        });
    }
}
