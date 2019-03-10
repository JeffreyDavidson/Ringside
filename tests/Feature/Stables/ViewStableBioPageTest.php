<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\User;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewStableBioPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_stable_profile()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.show', ['stable' => $stable]));

        $response->assertViewIs('stables.show');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /** @test */
    public function a_basic_user_can_view_their_stable_profile()
    {
        $signedInUser = $this->actAs('basic-user');

        $stable = factory(Stable::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('stables.show', ['stable' => $stable]));

        $response->assertOk();
    }

    /** @test */
    public function a_stables_data_can_be_seen_on_their_profile()
    {
        $signedInUser = $this->actAs('administrator');

        $stable = factory(Stable::class)->create([
            'name' => 'Example Stable Name',
        ]);

        $response = $this->get(route('stables.show', ['stable' => $stable]));

        $response->assertSee('Example Stable Name');
    }

    /** @test */
    public function a_guest_cannot_view_a_stable_profile()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.show', ['stable' => $stable]));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_stable_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $stable = factory(Stable::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('stables.show', ['stable' => $stable]));

        $response->assertStatus(403);
    }
}
