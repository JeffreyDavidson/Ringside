<?php

namespace Tests\Feature\SuperAdmin\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class RestoreRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_restore_a_deleted_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->create();
        $referee->delete();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertNull($referee->fresh()->deleted_at);
    }
}
