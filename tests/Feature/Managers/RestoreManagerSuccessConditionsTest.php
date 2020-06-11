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
class RestoreManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_restore_a_deleted_manager($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertNull($manager->fresh()->deleted_at);
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
