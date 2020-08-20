<?php

namespace Tests\Unit\Observers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class RefereeObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_referees_status_is_calculated_correctly()
    {
        $referee = RefereeFactory::new()->create();
        $this->assertEquals('unemployed', $referee->status);

        $referee->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('future-employment', $referee->status);

        $referee->employ(Carbon::today()->toDateTimeString());
        $this->assertEquals('bookable', $referee->status);

        $referee->injure();
        $this->assertEquals('injured', $referee->status);

        $referee->clearFromInjury();
        $this->assertEquals('bookable', $referee->status);

        $referee->suspend();
        $this->assertEquals('suspended', $referee->status);

        $referee->reinstate();
        $this->assertEquals('bookable', $referee->status);

        $referee->retire();
        $this->assertEquals('retired', $referee->status);

        $referee->unretire();
        $this->assertEquals('bookable', $referee->status);
    }
}
