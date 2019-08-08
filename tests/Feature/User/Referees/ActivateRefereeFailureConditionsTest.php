<?php

namespace Tests\Feature\User\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class ActivateInactiveRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_activate_a_pending_introduction_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('pending-introduction')->create();

        $response = $this->put(route('referees.activate', $referee));

        $response->assertForbidden();
    }
}
