<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class RetireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_a_bookable_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('roster.stables.retire', $stable));

        $response->assertForbidden();
    }
}
