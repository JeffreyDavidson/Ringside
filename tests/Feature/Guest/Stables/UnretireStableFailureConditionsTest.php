<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class UnretireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_unretire_a_retired_stable()
    {
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('stables.retire', $stable));

        $response->assertRedirect(route('login'));
    }
}
