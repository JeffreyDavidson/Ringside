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
class ReinstateManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function an_administrator_can_reinstate_a_suspended_manager($adminRoles)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals($now->toDateTimeString(), $manager->suspensions()->latest()->first()->ended_at);
        });
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
