<?php

namespace Tests\Feature\Wrestlers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->actingAs($user)
                        ->delete(route('wrestlers.destroy', $wrestler));

        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_wrestler()
    {
        $user = factory(User::class)->states('basic-user')->create();
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->actingAs($user)
                        ->delete(route('wrestlers.destroy', $wrestler));

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
