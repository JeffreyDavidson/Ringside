<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class ViewManagerBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_another_users_manager_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = UserFactory::new()->create();
        $manager = ManagerFactory::new()->create(['user_id' => $otherUser->id]);

        $response = $this->showRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_manager_profile()
    {
        $manager = ManagerFactory::new()->create();

        $response = $this->showRequest($manager);

        $response->assertRedirect(route('login'));
    }
}
