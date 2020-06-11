<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class SuspendManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_suspend_an_available_manager($adminRoles)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->available()->create();

        $response = $this->suspendRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals($now->toDateTimeString(), $manager->fresh()->currentSuspension->started_at);
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
