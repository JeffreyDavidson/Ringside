<?php

namespace Tests\Feature\User\Stables;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group users
 * @group roster
 */
class ViewStableBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_another_users_stable_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = UserFactory::new()->create();
        $stable = StableFactory::new()->create(['user_id' => $otherUser->id]);

        $response = $this->showRequest($stable);

        $response->assertForbidden();
    }
}
