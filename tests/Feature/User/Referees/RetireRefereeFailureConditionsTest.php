<?php

namespace Tests\Feature\User\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class RetireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_a_bookable_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_retire_an_injured_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_suspended_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertForbidden();
    }
}
