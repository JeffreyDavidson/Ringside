<?php

namespace Tests\Feature;

use App\User;
use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewWrestlerBioPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_wrestler_profile()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertViewIs('wrestlers.show');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_basic_user_can_view_their_wrestler_profile()
    {
        $signedInUser = $this->actAs('basic-user');

        $wrestler = factory(Wrestler::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertOk();
    }

    /** @test */
    public function a_wrestlers_data_can_be_seen_on_their_profile()
    {
        $signedInUser = $this->actAs('administrator');

        $wrestler = factory(Wrestler::class)->create([
            'name' => 'Wrestler 1',
            'height' => 78,
            'weight' => 220,
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
        ]);

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertSee('Wrestler 1');
        $response->assertSee(e('6\'6"'));
        $response->assertSee('220 lbs');
        $response->assertSee('Laraville, FL');
        $response->assertSee('The Finisher');
    }

    /** @test */
    public function a_guest_cannot_view_a_wrestler_profile()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_wrestler_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $wrestler = factory(Wrestler::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertStatus(403);
    }
}
