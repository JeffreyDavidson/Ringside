<?php

namespace Tests\Unit\Observers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group roster
 */
class WrestlerObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_wrestlers_status_is_calculated_correctly()
    {
        $wrestler = factory(Wrestler::class)->create();
        $this->assertEquals('pending-employment', $wrestler->status);

        $wrestler->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('pending-employment', $wrestler->status);

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
