<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class SuspendManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create();

        $response = $this->suspendRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $response = $this->suspendRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function some_managers_cannot_be_suspended($adminRoles, $managerStatusesCannotBeSuspended)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeSuspendedException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeSuspended()->create();

        $response = $this->suspendRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeSuspended()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeSuspended()
    {
        return [
            ['pendingEmployment'],
            ['retired'],
            ['suspended'],
        ];
    }
}
