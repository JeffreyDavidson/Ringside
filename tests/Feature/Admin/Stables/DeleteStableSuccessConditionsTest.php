<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class DeleteStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_bookable_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_pending_introduction_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_retired_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }
}
