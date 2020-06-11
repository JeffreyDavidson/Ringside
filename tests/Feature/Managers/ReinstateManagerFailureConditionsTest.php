<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class ReinstateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_manager()
    {
        $manager = ManagerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function an_available_manager_cannot_be_reinstated($adminRoles, $managerStatusesCannotBeReinstated)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeReinstated()->create();

        $response = $this->reinstateRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeReinstated()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeReinstated()
    {
        return [
            ['available'],
            ['pendingEmployment'],
            ['retired'],
            ['injured'],
        ];
    }
}
