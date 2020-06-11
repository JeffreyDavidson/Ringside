<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
 */
class UnretireManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->retired()->create();

        $response = $this->unretireRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_manager()
    {
        $manager = ManagerFactory::new()->retired()->create();

        $response = $this->unretireRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function some_managers_cannot_be_unretired($adminRoles, $managerStatusesCannotBeUnretired)
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatusesCannotBeUnretired()->create();

        $response = $this->unretireRequest($manager);

        $response->assertForbidden();
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatusesCannotBeUnretired()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatusesCannotBeUnretired()
    {
        return [
            ['available'],
            ['pendingEmployment'],
            ['injured'],
            ['suspended'],
        ];
    }
}
