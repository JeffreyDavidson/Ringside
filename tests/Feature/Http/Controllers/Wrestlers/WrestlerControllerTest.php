<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class WrestlerControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([WrestlersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeLivewire('wrestlers.bookable-wrestlers')
            ->assertSeeLivewire('wrestlers.future-employed-and-unemployed-wrestlers')
            ->assertSeeLivewire('wrestlers.released-wrestlers')
            ->assertSeeLivewire('wrestlers.suspended-wrestlers')
            ->assertSeeLivewire('wrestlers.injured-wrestlers')
            ->assertSeeLivewire('wrestlers.retired-wrestlers');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_wrestlers_index_page()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([WrestlersController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_wrestlers_index_page()
    {
        $this
            ->get(action([WrestlersController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_returns_a_view()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([WrestlersController::class, 'show'], $wrestler))
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler);
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_their_wrestler_profile()
    {
        $this->actAs(Role::basic());
        $wrestler = Wrestler::factory()->create(['user_id' => auth()->user()]);

        $this
            ->get(action([WrestlersController::class, 'show'], $wrestler))
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_another_users_wrestler_profile()
    {
        $otherUser = User::factory()->create();
        $wrestler = Wrestler::factory()->create(['user_id' => $otherUser->id]);

        $this
            ->actAs(Role::basic())
            ->get(action([WrestlersController::class, 'show'], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_wrestler_profile()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->get(action([WrestlersController::class, 'show'], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function deletes_a_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->delete(action([WrestlersController::class, 'destroy'], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        $this->assertSoftDeleted($wrestler);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
            ->delete(action([WrestlersController::class, 'destroy'], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->delete(action([WrestlersController::class, 'destroy'], $wrestler))
            ->assertRedirect(route('login'));
    }
}
