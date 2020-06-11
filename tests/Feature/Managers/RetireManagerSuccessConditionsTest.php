<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class RetireManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider providers
     */
    public function administrators_can_retire_managers($adminRoles, $managerStatuses)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatuses()->create();

        $response = $this->retireRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->currentRetirement->started_at);
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatuses()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatuses()
    {
        return [
            ['available'],
            ['suspended'],
            ['injured'],
        ];
    }
}
