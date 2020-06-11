<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class RetireManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /*
     * @test
     * @dataProvider managerStatuses
     */
    public function a_basic_user_cannot_retire_managers($managerStatuses)
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->$managerStatuses()->create();

        $response = $this->retireRequest($manager);

        $response->assertForbidden();
    }

    /*
     * @test
     * @dataProvider managerStatuses
     */
    public function a_guest_cannot_retire_managers($managerStatuses)
    {
        $manager = ManagerFactory::new()->$managerStatuses()->create();

        $response = $this->retireRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function a_retired_manager_cannot_be_retired($adminRoles, $managerStatusesCannotBeRetired)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeRetiredException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeRetired()->create();

        $response = $this->retireRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeRetired()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeRetired()
    {
        return [
            ['pendingEmployment'],
            ['retired'],
        ];
    }

    public function managerStatuses()
    {
        return [
            ['available'],
            ['pendingEmployment'],
            ['retired'],
            ['suspended'],
            ['injured'],
        ];
    }
}
