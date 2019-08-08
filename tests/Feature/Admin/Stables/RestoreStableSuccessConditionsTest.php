<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class RestoreStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $stable->delete();

        $response = $this->put(route('roster.stables.restore', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertNull($stable->fresh()->deleted_at);
    }
}
