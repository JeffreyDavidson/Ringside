<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeInjuredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class InjureManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_injure_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create();

        $response = $this->injureRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $response = $this->injureRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function an_injured_manager_cannot_be_injured($adminRoles, $managerStatusesCannotBeInjured)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeInjured()->create();

        $response = $this->injureRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeInjured()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeInjured()
    {
        return [
            ['pendingEmployment'],
            ['retired'],
            ['suspended'],
            ['injured'],
        ];
    }
}
