<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class UnretireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('roster.stables.unretire', $stable));

        $response->assertForbidden();
    }
}
