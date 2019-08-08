<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class DeleteStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_a_bookable_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_pending_introduction_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_retired_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->delete(route('roster.stables.destroy', $stable));

        $response->assertForbidden();
    }
}
