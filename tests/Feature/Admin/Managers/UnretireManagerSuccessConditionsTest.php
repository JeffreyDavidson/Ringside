<?php

namespace Tests\Feature\Admin\Managers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group admins
 */
class UnretireManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_manager()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->put(route('managers.unretire', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals($now->toDateTimeString(), $manager->fresh()->retirements()->latest()->first()->ended_at);
    }
}
