<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class ViewStableBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_can_view_their_stable_profile()
    {
        $signedInUser = $this->actAs('basic-user');
        $stable = factory(Stable::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('roster.stables.show', $stable));

        $response->assertOk();
    }
}
