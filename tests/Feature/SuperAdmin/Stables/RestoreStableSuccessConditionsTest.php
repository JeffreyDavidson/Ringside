<?php

namespace Tests\Feature\SuperAdmin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group superadmins
 */
class RestoreStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_restore_a_deleted_stable()
    {
        $this->actAs('super-administrator');
        $stable = factory(Stable::class)->create();
        $stable->delete();

        $response = $this->put(route('roster.stables.restore', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertNull($stable->fresh()->deleted_at);
    }
}
