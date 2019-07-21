<?php

namespace Tests\Feature\Generic\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class ActivateRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_referee_cannot_be_activated()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.activate', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_referee_cannot_be_activated()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.activate', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_activated()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->put(route('referees.activate', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_referee_cannot_be_activated()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->put(route('referees.activate', $referee));

        $response->assertForbidden();
    }
}
