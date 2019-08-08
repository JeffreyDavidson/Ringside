<?php

namespace Tests\Feature\Guest\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group guests
 */
class ActivateRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_activate_a_pending_introduction_referee()
    {
        $referee = factory(Referee::class)->states('pending-introduction')->create();

        $response = $this->put(route('referees.activate', $referee));

        $response->assertRedirect(route('login'));
    }
}
