<?php

namespace Tests\Feature;

use App\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('referees.restore', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertNull($referee->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('referees.restore', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_referee()
    {
        $referee = factory(Referee::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('referees.restore', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_referee_cannot_be_restored()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.restore', $referee));

        $response->assertStatus(404);
    }
}
