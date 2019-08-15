<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class ActivateStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_activate_a_pending_introduction_stable()
    {
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.activate', $stable));

        $response->assertRedirect(route('login'));
    }
}
