<?php

namespace Tests\Feature\Generic\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 * @group roster
 */
class UnretireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_referee_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_referee_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('pending-employment')->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_referee_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->unretireRequest($referee);

        $response->assertForbidden();
    }
}
