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
class DeleteManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider providers
     */
    public function administrators_can_delete_managers($adminRoles, $managerStatuses)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->$managerStatuses()->create();

        $response = $this->deleteRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted($manager);
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
            ['pendingEmployment'],
            ['retired'],
            ['suspended'],
            ['injured'],
        ];
    }
}
