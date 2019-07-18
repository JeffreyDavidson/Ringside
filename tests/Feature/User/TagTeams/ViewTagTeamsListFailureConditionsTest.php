<?php

namespace Tests\Feature\User\TagTeams;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class ViewTagTeamsListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_tag_teams_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('tagteams.index'));

        $response->assertForbidden();
    }
}
