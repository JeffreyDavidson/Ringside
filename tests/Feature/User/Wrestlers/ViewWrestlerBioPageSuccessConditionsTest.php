<?php

namespace Tests\Feature\User\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewWrestlerBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_can_view_their_wrestler_profile()
    {
        $signedInUser = $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertOk();
    }
}
