<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoke_restores_a_soft_deleted_manager_and_redirects()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $manager = Manager::factory()->softDeleted()->create();

        $response = $this->restoreRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertNull($manager->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->softDeleted()->create();

        $this->restoreRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_manager()
    {
        $manager = Manager::factory()->softDeleted()->create();

        $this->restoreRequest($manager)->assertRedirect(route('login'));
    }
}
