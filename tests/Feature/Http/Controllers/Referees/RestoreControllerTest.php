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
        $this->actAs(Role::ADMINISTRATOR);
        $referee = Referee::factory()->softDeleted()->create();

        $response = $this->restoreRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertNull($referee->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->softDeleted()->create();

        $this->restoreRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_referee()
    {
        $referee = Referee::factory()->softDeleted()->create();

        $this->restoreRequest($referee)->assertRedirect(route('login'));
    }
}