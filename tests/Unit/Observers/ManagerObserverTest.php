<?php

namespace Tests\Unit\Observers;

use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 * @group observers
 */
class ManagerObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_managers_status_is_calculated_correctly()
    {
        $manager = Manager::factory()->unemployed()->create();
        $this->assertEquals('unemployed', $manager->status);

        $manager->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('future-employment', $manager->status);

        $manager->employ(Carbon::today()->toDateTimeString());
        $this->assertEquals('available', $manager->status);

        $manager->injure();
        $this->assertEquals('injured', $manager->status);

        $manager->clearFromInjury();
        $this->assertEquals('available', $manager->status);

        $manager->suspend();
        $this->assertEquals('suspended', $manager->status);

        $manager->reinstate();
        $this->assertEquals('available', $manager->status);

        $manager->retire();
        $this->assertEquals('retired', $manager->status);

        $manager->unretire();
        $this->assertEquals('available', $manager->status);
    }
}
