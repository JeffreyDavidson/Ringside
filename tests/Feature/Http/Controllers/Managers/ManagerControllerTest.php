<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use App\Models\User;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class ManagerControllerTest extends TestCase
{
    /**
     * @test
     */
    public function index_returns_a_views()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([ManagersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('managers.index')
            ->assertSeeLivewire('managers.managers-list');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_managers_index_page()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([ManagersController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_managers_index_page()
    {
        $this
            ->get(action([ManagersController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_can_view_a_manager_profile()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([ManagersController::class, 'show'], $manager))
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $manager);
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_their_manager_profile()
    {
        $this->actAs(Role::basic());
        $manager = Manager::factory()->create(['user_id' => auth()->user()]);

        $this
            ->get(action([ManagersController::class, 'show'], $manager))
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_another_users_manager_profile()
    {
        $otherUser = User::factory()->create();
        $manager = Manager::factory()->create(['user_id' => $otherUser->id]);

        $this
            ->actAs(Role::basic())
            ->get(action([ManagersController::class, 'show'], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_manager_profile()
    {
        $manager = Manager::factory()->create();

        $this
            ->get(action([ManagersController::class, 'show'], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function delete_a_manager_and_redirects()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->delete(action([ManagersController::class, 'destroy'], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        $this->assertSoftDeleted($manager);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::basic())
            ->delete(route('managers.destroy', $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->delete(action([ManagersController::class, 'destroy'], $manager))
            ->assertRedirect(route('login'));
    }
}
