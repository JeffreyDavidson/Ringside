<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect('/login');
    }
}
