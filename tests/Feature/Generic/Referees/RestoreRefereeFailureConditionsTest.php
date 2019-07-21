<?php

namespace Tests\Feature\Generic\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class RestoreRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_referee_cannot_be_restored()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_restored()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_referee_cannot_be_restored()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertNotFound();
    }
    
    /** @test */
    public function a_pending_introduced_referee_cannot_be_restored()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('pending-introduced')->create();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertNotFound();
    }

    /** @test */
    public function an_injured_referee_cannot_be_restored()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertNotFound();
    }
}
