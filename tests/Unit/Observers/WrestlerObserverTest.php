<?php

namespace Tests\Unit\Observers;

use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 * @group observers
 */
class WrestlerObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_wrestlers_status_is_calculated_correctly()
    {
        $wrestler = Wrestler::factory()->create();
        $this->assertEquals('unemployed', $wrestler->status);

        $wrestler->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('future-employment', $wrestler->status);

        $wrestler->employ(Carbon::today()->toDateTimeString());
        $this->assertEquals('bookable', $wrestler->status);

        $wrestler->injure();
        $this->assertEquals('injured', $wrestler->status);

        $wrestler->clearFromInjury();
        $this->assertEquals('bookable', $wrestler->status);

        $wrestler->suspend();
        $this->assertEquals('suspended', $wrestler->status);

        $wrestler->reinstate();
        $this->assertEquals('bookable', $wrestler->status);

        $wrestler->retire();
        $this->assertEquals('retired', $wrestler->status);

        $wrestler->unretire();
        $this->assertEquals('bookable', $wrestler->status);
    }
}
