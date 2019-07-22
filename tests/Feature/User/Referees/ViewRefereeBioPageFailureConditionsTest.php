<?php

namespace Tests\Feature\User\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class ViewRefereeBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_a_referee_profile()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.show', ['referee' => $referee]));

        $response->assertForbidden();
    }
}
