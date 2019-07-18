<?php

namespace Tests\Feature\Guest\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
 */
class SuspendTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_suspend_a_tagteam()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->put(route('tagteams.suspend', $tagteam));

        $response->assertRedirect(route('login'));
    }
}
