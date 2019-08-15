<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class RestoreStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_restore_a_deleted_stable()
    {
        $stable = factory(Stable::class)->create();
        $stable->delete();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertRedirect(route('login'));
    }
}
