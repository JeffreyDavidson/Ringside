<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedStableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('stables.restore', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertNull($stable->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('stables.restore', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_stable()
    {
        $stable = factory(Stable::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('stables.restore', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_stable_cannot_be_restored()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.restore', $stable));

        $response->assertStatus(404);
    }
}
