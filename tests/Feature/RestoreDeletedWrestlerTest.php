<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNull($wrestler->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertStatus(404);
    }
}
