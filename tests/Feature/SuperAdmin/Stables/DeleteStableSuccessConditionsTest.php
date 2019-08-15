<?php

namespace Tests\Feature\SuperAdmin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group superadmins
 */
class DeleteStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_delete_a_bookable_stable()
    {
        $this->actAs('super-administrator');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->delete(route('stables.destroy', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_a_pending_introduction_stable()
    {
        $this->actAs('super-administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->delete(route('stables.destroy', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_a_retired_stable()
    {
        $this->actAs('super-administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('stables.destroy', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted('stables', ['name' => $stable->name]);
    }
}
