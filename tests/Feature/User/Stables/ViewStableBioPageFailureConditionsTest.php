<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\User;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class ViewStableBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_another_users_stable_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $stable = factory(Stable::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('roster.stables.show', $stable));

        $response->assertForbidden();
    }
}
