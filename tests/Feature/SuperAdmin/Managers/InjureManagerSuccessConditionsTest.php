<?php

namespace Tests\Feature\SuperAdmin\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group superadmins
 */
class InjureManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_injure_a_bookable_manager()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.injure', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->injury->started_at);
    }
}
