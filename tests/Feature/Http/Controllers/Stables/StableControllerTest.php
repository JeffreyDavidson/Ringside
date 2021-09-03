<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class StableControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([StablesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire('stables.active-stables')
            ->assertSeeLivewire('stables.future-activation-and-unactivated-stables')
            ->assertSeeLivewire('stables.inactive-stables')
            ->assertSeeLivewire('stables.retired-stables');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_stables_index_page()
    {
        $this
            ->actAs(Role::BASIC)
            ->get(action([StablesController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_stables_index_page()
    {
        $this
            ->get(action([StablesController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_returns_a_view()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([StablesController::class, 'show'], $stable))
            ->assertViewIs('stables.show')
            ->assertViewHas('stable', $stable);
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_their_stable_profile()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create(['user_id' => auth()->user()]);

        $this
            ->get(action([StablesController::class, 'show'], $stable))
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_another_users_stable_profile()
    {
        $otherUser = User::factory()->create();
        $stable = Stable::factory()->create(['user_id' => $otherUser->id]);

        $this
            ->actAs(Role::BASIC)
            ->get(action([StablesController::class, 'show'], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_stable_profile()
    {
        $stable = Stable::factory()->create();

        $this
            ->get(action([StablesController::class, 'show'], $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function deletes_a_stable_and_redirects()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->delete(action([StablesController::class, 'destroy'], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        $this->assertSoftDeleted($stable);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->delete(action([StablesController::class, 'destroy'], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->delete(action([StablesController::class, 'destroy'], $stable))
            ->assertRedirect(route('login'));
    }
}
