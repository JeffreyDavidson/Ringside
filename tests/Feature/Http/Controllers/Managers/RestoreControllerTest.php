<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\RestoreController;
use App\Models\Manager;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    public Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = Manager::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_soft_deleted_manager_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->patch(action([RestoreController::class], $this->manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        $this->assertNull($this->manager->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_manager()
    {
        $this
            ->actAs(Role::basic())
            ->patch(action([RestoreController::class], $this->manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_manager()
    {
        $this
            ->patch(action([RestoreController::class], $this->manager))
            ->assertRedirect(route('login'));
    }
}
