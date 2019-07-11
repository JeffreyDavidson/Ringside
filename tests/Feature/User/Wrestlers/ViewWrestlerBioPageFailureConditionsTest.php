<?php

namespace Tests\Feature\User\Wrestlers;

use App\Models\User;
use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewWrestlerBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_another_users_wrestler_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $wrestler = factory(Wrestler::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertForbidden();
    }
}
