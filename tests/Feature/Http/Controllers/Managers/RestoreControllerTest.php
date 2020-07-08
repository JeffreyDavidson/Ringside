<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\ManagerFactory;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoke_restores_a_deleted_manager_and_redirects()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $manager = ManagerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertNull($manager->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->softDeleted()->create();

        $this->restoreRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_manager()
    {
        $manager = ManagerFactory::new()->softDeleted()->create();

        $this->restoreRequest($manager)->assertRedirect(route('login'));
    }
}
