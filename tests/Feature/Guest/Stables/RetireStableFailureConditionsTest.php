<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class RetireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_retire_a_bookable_stable()
    {
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('roster.stables.retire', $stable));

        $response->assertRedirect(route('login'));
    }
}
