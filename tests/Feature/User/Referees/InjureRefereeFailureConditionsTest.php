<?php

namespace Tests\Feature\User\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class InjureRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_injure_a_bookable_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.injure', $referee));

        $response->assertForbidden();
    }
}
