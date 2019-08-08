<?php

namespace Tests\Feature\Generic\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class RecoverRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_referee_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.recover', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_referee_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('pending-introduction')->create();

        $response = $this->put(route('referees.recover', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_referee_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.recover', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->put(route('referees.recover', $referee));

        $response->assertForbidden();
    }
}
