<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class DeleteManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider managerStatuses
     */
    public function a_basic_user_cannot_delete_managers($managerStatuses)
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->$managerStatuses()->create();

        $response = $this->deleteRequest($manager);

        $response->assertForbidden();
    }

    /*
     * @test
     * @dataProvider managerStatuses
     */
    public function a_guest_cannot_delete_managers($managerStatuses)
    {
        $manager = ManagerFactory::new()->$managerStatuses()->create();

        $response = $this->deleteRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_deleted_manager_cannot_be_deleted()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $manager = ManagerFactory::new()->softDeleted()->create();

        $response = $this->deleteRequest($manager);

        $response->assertNotFound();
    }

    public function managerStatuses()
    {
        return [
            ['available'],
            ['pendingEmployment'],
            ['retired'],
            ['injured'],
            ['suspended']
        ];
    }
}
