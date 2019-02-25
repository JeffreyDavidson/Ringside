<?php

namespace Tests\Feature;

use App\User;
use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewManagerBioPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_manager_profile()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.show', ['manager' => $manager]));

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_basic_user_can_view_their_manager_profile()
    {
        $signedInUser = $this->actAs('basic-user');

        $manager = factory(Manager::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('managers.show', ['manager' => $manager]));

        $response->assertOk();
    }

    /** @test */
    public function a_managers_data_can_be_seen_on_their_profile()
    {
        $signedInUser = $this->actAs('administrator');

        $manager = factory(Manager::class)->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        $response = $this->get(route('managers.show', ['manager' => $manager]));

        $response->assertSee('John Smith');
    }

    /** @test */
    public function a_guest_cannot_view_a_manager_profile()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.show', ['manager' => $manager]));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_manager_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $manager = factory(Manager::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('managers.show', ['manager' => $manager]));

        $response->assertStatus(403);
    }
}
