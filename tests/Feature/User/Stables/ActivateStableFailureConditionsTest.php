<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class ActivateStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_activate_a_pending_introduction_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('roster.stables.activate', $stable));

        $response->assertForbidden();
    }
}
