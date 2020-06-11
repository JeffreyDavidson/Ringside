<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class EmployManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_employ_a_pending_employment_manager($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function (Manager $manager) {
            $this->assertTrue($manager->isCurrentlyEmployed());
        });
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function a_manager_without_a_current_employment_can_be_employed($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertTrue($manager->currentEmployment()->exists());
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
