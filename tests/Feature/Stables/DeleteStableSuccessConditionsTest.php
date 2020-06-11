<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 */
class DeleteStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_bookable_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->deleteRequest($stable);

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_pending_introduction_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = StableFactory::new()->pendingIntroduction()->create();

        $response = $this->deleteRequest($stable);

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_retired_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = StableFactory::new()->retired()->create();

        $response = $this->deleteRequest($stable);

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }
}
