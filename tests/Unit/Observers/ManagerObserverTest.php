<?php

namespace Tests\Unit\Observers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group roster
 */
class ManagerObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_managers_status_is_calculated_correctly()
    {
        $manager = factory(Manager::class)->create();
        $this->assertEquals('pending-employment', $manager->status);

        $manager->employ(Carbon::tomorrow()->toDateTimeString());
        $this->assertEquals('pending-employment', $manager->status);

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
