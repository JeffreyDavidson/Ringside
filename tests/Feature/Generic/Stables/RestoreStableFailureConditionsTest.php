<?php

namespace Tests\Feature\Generic\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class RestoreStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_stable_cannot_be_restored()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_introduction_stable_cannot_be_restored()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_stable_cannot_be_restored()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertNotFound();
    }
}
