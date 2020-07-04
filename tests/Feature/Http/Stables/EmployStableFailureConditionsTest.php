<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\Stable;
use Tests\Factories\StableFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $stable = StableFactory::new()->active()->create();

        $response = $this->put(route('stables.employ', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_stable_cannot_be_employed()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = StableFactory::new()->states('retired')->create();

        $response = $this->put(route('stables.employ', $stable));

        $response->assertForbidden();
    }
}
