<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class RefereeControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([RefereesController::class, 'index']))
            ->assertOk(Role::administrator())
            ->assertViewIs('referees.index')
            ->assertSeeLivewire('referees.employed-referees')
            ->assertSeeLivewire('referees.future-employed-and-unemployed-referees')
            ->assertSeeLivewire('referees.released-referees')
            ->assertSeeLivewire('referees.suspended-referees')
            ->assertSeeLivewire('referees.injured-referees')
            ->assertSeeLivewire('referees.retired-referees');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_referees_index_page()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([RefereesController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_referees_index_page()
    {
        $this
            ->get(action([RefereesController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_returns_a_view()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([RefereesController::class, 'show'], $referee))
            ->assertViewIs('referees.show')
            ->assertViewHas('referee', $referee);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_a_referee_profile()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([RefereesController::class, 'show'], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_referee_profile()
    {
        $referee = Referee::factory()->create();

        $this
            ->get(action([RefereesController::class, 'show'], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function an_administrator_can_delete_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->delete(action([RefereesController::class, 'destroy'], $referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        $this->assertSoftDeleted($referee);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(Role::basic())
            ->delete(action([RefereesController::class, 'destroy'], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->delete(action([RefereesController::class, 'destroy'], $referee))
            ->assertRedirect(route('login'));
    }
}
