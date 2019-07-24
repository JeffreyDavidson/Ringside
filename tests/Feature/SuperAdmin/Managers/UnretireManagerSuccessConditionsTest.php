<?php

namespace Tests\Feature\SuperAdmin\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group superadmins
 */
class UnretireManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_unretire_a_retired_manager()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->put(route('managers.unretire', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->retirements()->latest()->first()->ended_at);
    }
}
