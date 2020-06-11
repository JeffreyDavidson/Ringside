<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 */
class EmployStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_stable_cannot_be_employed()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('stables.employ', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_stable_cannot_be_employed()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('stables.employ', $stable));

        $response->assertForbidden();
    }
}
