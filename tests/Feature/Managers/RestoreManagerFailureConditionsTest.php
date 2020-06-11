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
class RestoreManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_manager()
    {
        $manager = ManagerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function an_available_manager_cannot_be_restored($adminRoles, $managerStatusesCannotBeRestored)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeRestored()->create();

        $response = $this->restoreRequest($manager);

        $response->assertNotFound();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeRestored()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeRestored()
    {
        return [
            ['available'],
            ['pendingEmployment'],
            ['retired'],
            ['injured'],
            ['suspended'],
        ];
    }
}
