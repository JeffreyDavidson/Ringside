<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoke_restores_a_deleted_referee_and_redirects()
    {
        $referee = Referee::factory()->softDeleted()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(route('referees.restore', $referee))
            ->assertRedirect(route('referees.index'));

        $this->assertNull($referee->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_referee()
    {
        $referee = Referee::factory()->softDeleted()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('referees.restore', $referee))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_referee()
    {
        $referee = Referee::factory()->softDeleted()->create();

        $this->patch(route('referees.restore', $referee))
            ->assertRedirect(route('login'));
    }
}
