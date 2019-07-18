<?php

namespace Tests\Feature\Guest\TagTeams;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
 */
class ViewTagTeamsListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_tag_teams_page()
    {
        $response = $this->get(route('tagteams.index'));

        $response->assertRedirect(route('login'));
    }
}
