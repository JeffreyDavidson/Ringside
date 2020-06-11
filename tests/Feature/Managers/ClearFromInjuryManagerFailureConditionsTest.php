<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class ClearFromInjuryManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_clear_manager_from_injury()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_clear_manager_from_injury()
    {
        $manager = ManagerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function an_available_manager_cannot_be_cleared_from_an_injury($adminRoles, $managerStatusesCannotBeCleared)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeCleared()->create();

        $response = $this->clearInjuryRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeCleared()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeCleared()
    {
        return [
            ['available'],
            ['pendingEmployment'],
            ['retired'],
            ['suspended'],
        ];
    }
}
