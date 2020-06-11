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
class UnretireManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_unretire_a_retired_manager($adminRoles)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->retired()->create();

        $response = $this->unretireRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals($now->toDateTimeString(), $manager->fresh()->retirements()->latest()->first()->ended_at);
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
