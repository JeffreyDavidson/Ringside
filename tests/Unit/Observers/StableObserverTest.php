<?php

namespace Tests\Unit\Observers;

use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group observers
 */
class StableObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_stable_status_is_calculated_correctly()
    {
        $stable = Stable::factory()->unactivated()->create();
        $this->assertEquals('unactivated', $stable->status);

        $stable->activate(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('future-activation', $stable->status);

        $stable->activate(Carbon::today()->toDateTimeString());
        $this->assertEquals('active', $stable->status);

        $stable->deactivate();
        $this->assertEquals('inactive', $stable->status);

        $stable->retire();
        $this->assertEquals('retired', $stable->status);
    }
}
