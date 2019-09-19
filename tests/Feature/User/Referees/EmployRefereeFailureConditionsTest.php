<?php

namespace Tests\Feature\User\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 * @group roster
 */
class EmployInactiveRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_employ_a_pending_employment_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('pending-employment')->create();

        $response = $this->employRequest($referee);

        $response->assertForbidden();
    }
}
