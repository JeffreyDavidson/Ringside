<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class DeleteStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_delete_a_bookable_stable()
    {
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_pending_introduction_stable()
    {
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_retired_stable()
    {
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertRedirect(route('login'));
    }
}
