<?php

namespace Tests\Feature\Generic\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class RetireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_pending_introduction_stable_cannot_be_retired()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('roster.stables.retire', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_stable_cannot_be_retired()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('roster.stables.retire', $stable));

        $response->assertForbidden();
    }
}
