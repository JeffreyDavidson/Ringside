<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class EmployManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_employ_a_pending_employment_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_pending_employment_manager()
    {
        $manager = ManagerFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function some_managers_statuses_cannot_be_employed($adminRoles, $managerStatusesCannotBeEmployed)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeEmployed()->create();

        $response = $this->employRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeEmployed()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeEmployed()
    {
        return [
            ['available'],
            ['retired'],
            ['suspended'],
            ['injured'],
        ];
    }
}
