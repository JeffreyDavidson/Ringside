<?php

namespace Tests\Feature\User\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class UnretireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.unretire', $referee));

        $response->assertForbidden();
    }
}
